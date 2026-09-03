<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\Sheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillOpeningBalancesTest extends TestCase
{
    use RefreshDatabase;

    protected string $path;

    protected function setUp(): void
    {
        parent::setUp();

        Sheet::create(['name' => 'Sheet 1']);
        $this->path = sys_get_temp_dir().'/opening-backfill-'.getmypid().'.csv';
    }

    protected function tearDown(): void
    {
        @unlink($this->path);

        parent::tearDown();
    }

    protected function customer(string $serial): Customer
    {
        return Customer::create([
            'sheet_id'        => 1,
            'serial_no'       => $serial,
            'name'            => 'Customer '.$serial,
            'mobile_number'   => '0171000'.$serial,
            'address'         => 'Jaforabad',
            'meter_number'    => 'MTR-'.$serial,
            'connection_type' => 'residential',
            'connection_date' => '2025-01-10',
            'status'          => Customer::STATUS_ACTIVE,
        ]);
    }

    protected function csv(string $contents): void
    {
        file_put_contents($this->path, "serial_no,opening_reading,opening_due,opening_as_of\n".$contents);
    }

    public function test_it_backfills_a_customer_entered_before_opening_balances_existed(): void
    {
        $customer = $this->customer('2001');
        $this->csv("2001,4820,1250,2026-08-31\n");

        $this->artisan('customers:backfill-opening', ['file' => $this->path])
            ->assertSuccessful();

        $customer->refresh();
        $this->assertSame('4820.00', $customer->openingReadingRow->current_reading);
        $this->assertSame('1250.00', $customer->openingBill->due_amount);
    }

    public function test_a_dry_run_writes_nothing(): void
    {
        $customer = $this->customer('2001');
        $this->csv("2001,4820,1250,2026-08-31\n");

        $this->artisan('customers:backfill-opening', ['file' => $this->path, '--dry-run' => true])
            ->assertSuccessful();

        $this->assertNull($customer->refresh()->opening_as_of);
        $this->assertSame(0, Bill::count());
        $this->assertSame(0, MeterReading::count());
    }

    /** Rewriting the ledger under a customer who has already been billed would corrupt it. */
    public function test_it_skips_a_customer_who_has_already_been_read(): void
    {
        $customer = $this->customer('2001');
        MeterReading::create([
            'customer_id'      => $customer->id,
            'previous_reading' => 0,
            'current_reading'  => 100,
            'consumed_units'   => 100,
            'reading_date'     => '2026-09-30',
            'status'           => MeterReading::STATUS_PENDING,
            'source'           => MeterReading::SOURCE_MANUAL,
        ]);

        $this->csv("2001,4820,1250,2026-08-31\n");

        $this->artisan('customers:backfill-opening', ['file' => $this->path])
            ->assertSuccessful();

        // No anchor injected underneath the reading they already have.
        $this->assertNull($customer->refresh()->opening_as_of);
        $this->assertSame(0, MeterReading::where('source', MeterReading::SOURCE_OPENING)->count());
    }

    /**
     * A customer billed from zero before opening balances existed cannot be
     * retro-anchored: the units on their existing bills were charged against
     * the old chain and an anchor would not undo that.
     */
    public function test_it_skips_a_customer_who_has_already_been_billed(): void
    {
        $customer = $this->customer('2001');
        Bill::create([
            'customer_id'   => $customer->id,
            'billing_month' => '2026-08-01',
            'units'         => 4820,
            'total_amount'  => 48200,
            'due_amount'    => 48200,
            'status'        => Bill::STATUS_UNPAID,
        ]);

        $this->csv("2001,4820,1250,2026-08-31\n");

        $this->artisan('customers:backfill-opening', ['file' => $this->path])
            ->expectsOutputToContain('already been billed')
            ->assertSuccessful();

        $this->assertNull($customer->refresh()->opening_as_of);
        $this->assertSame(1, Bill::count());
    }

    public function test_it_reports_an_unmatched_row(): void
    {
        $this->csv("9999,4820,1250,2026-08-31\n");

        $this->artisan('customers:backfill-opening', ['file' => $this->path])
            ->expectsOutputToContain('no customer matched')
            ->assertSuccessful();
    }

    public function test_it_refuses_an_incomplete_row(): void
    {
        $this->customer('2001');
        $this->csv("2001,4820,,\n");

        $this->artisan('customers:backfill-opening', ['file' => $this->path])
            ->assertSuccessful();

        $this->assertSame(0, Bill::count());
    }
}
