<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\Sheet;
use App\Models\Tariff;
use App\Models\User;
use App\Services\BillGenerator;
use App\Services\CustomerOpeningBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOpeningBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerOpeningBalance $opening;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opening = app(CustomerOpeningBalance::class);

        Sheet::create(['name' => 'Sheet 1']);
        Tariff::create([
            'connection_type'  => 'residential',
            'per_unit_rate'    => 10,
            'line_charge'      => 0,
            'service_charge'   => 0,
            'demand_charge'    => 0,
            'electricity_duty' => 0,
            'status'           => Tariff::STATUS_ACTIVE,
        ]);
    }

    protected function customer(array $attributes = []): Customer
    {
        return Customer::create($attributes + [
            'sheet_id'        => 1,
            'serial_no'       => '2001',
            'name'            => 'Kamrul Islam',
            'mobile_number'   => '01710000001',
            'address'         => 'Jaforabad',
            'meter_number'    => 'MTR-2001',
            'connection_type' => 'residential',
            'connection_date' => '2025-01-10',
            'status'          => Customer::STATUS_ACTIVE,
        ]);
    }

    /** A customer carried over with a meter history and a debt. */
    protected function migrated(float $reading = 4820, float $due = 1250): Customer
    {
        $customer = $this->customer([
            'opening_reading' => $reading,
            'opening_due'     => $due,
            'opening_as_of'   => '2026-08-31',
        ]);

        $this->opening->materialize($customer);

        return $customer->refresh();
    }

    public function test_it_writes_an_anchor_reading_and_a_carry_forward_bill(): void
    {
        $customer = $this->migrated();

        $reading = MeterReading::where('customer_id', $customer->id)->sole();
        $this->assertSame('4820.00', $reading->current_reading);
        $this->assertSame('4820.00', $reading->previous_reading);
        $this->assertSame('0.00', $reading->consumed_units);
        $this->assertSame(MeterReading::SOURCE_OPENING, $reading->source);
        $this->assertTrue($reading->isCompleted());

        $bill = Bill::where('customer_id', $customer->id)->sole();
        $this->assertTrue($bill->is_opening);
        $this->assertSame('1250.00', $bill->due_amount);
        $this->assertSame('1250.00', $bill->total_amount);
        $this->assertNull($bill->meter_reading_id);
    }

    /**
     * The whole point of the anchor: the first real bill charges the month's
     * units, not everything the meter has ever counted.
     */
    public function test_the_first_real_bill_charges_only_the_units_since_handover(): void
    {
        $customer = $this->migrated();

        $reading = MeterReading::create([
            'customer_id'      => $customer->id,
            'previous_reading' => 4820,
            'current_reading'  => 4920,
            'consumed_units'   => 100,
            'reading_date'     => '2026-09-30',
            'status'           => MeterReading::STATUS_PENDING,
            'source'           => MeterReading::SOURCE_MANUAL,
        ]);

        $bill = app(BillGenerator::class)->generateForReading($reading);

        $this->assertSame('100.00', $bill->units);
        $this->assertSame('1000.00', $bill->energy_charge);
        // The carried debt rolls forward untouched...
        $this->assertSame('1250.00', $bill->previous_outstanding);
        // ...and is not penalised: it was never overdue under this system.
        $this->assertSame('0.00', $bill->late_fee);
        $this->assertSame('2250.00', $bill->total_amount);
    }

    /** From the month after, an unpaid balance is an ordinary late balance. */
    public function test_the_second_bill_penalises_the_balance_normally(): void
    {
        $customer = $this->migrated();

        foreach ([['2026-09-30', 4820, 4920], ['2026-10-31', 4920, 5020]] as [$date, $prev, $current]) {
            $reading = MeterReading::create([
                'customer_id'      => $customer->id,
                'previous_reading' => $prev,
                'current_reading'  => $current,
                'consumed_units'   => $current - $prev,
                'reading_date'     => $date,
                'status'           => MeterReading::STATUS_PENDING,
                'source'           => MeterReading::SOURCE_MANUAL,
            ]);
            $bill = app(BillGenerator::class)->generateForReading($reading);
        }

        // Carried 2250 from an unpaid September bill -> 10% penalty.
        $this->assertSame('2250.00', $bill->previous_outstanding);
        $this->assertSame('225.00', $bill->late_fee);
    }

    public function test_the_opening_bill_is_never_floored_at_the_minimum_charge(): void
    {
        $bill = $this->migrated(4820, 0)->openingBill;

        // BillCalculator would have floored a zero-unit residential bill at 300.
        $this->assertSame('0.00', $bill->energy_charge);
        $this->assertSame('0.00', $bill->total_amount);
        $this->assertTrue($bill->isPaid());
    }

    public function test_materialising_twice_does_not_double_the_debt(): void
    {
        $customer = $this->migrated();

        $this->opening->materialize($customer);
        $this->opening->materialize($customer);

        $this->assertSame(1, Bill::where('customer_id', $customer->id)->count());
        $this->assertSame(1, MeterReading::where('customer_id', $customer->id)->count());
    }

    public function test_a_new_connection_gets_no_opening_rows(): void
    {
        $customer = $this->customer();

        $this->assertNull($this->opening->materialize($customer));
        $this->assertSame(0, Bill::where('customer_id', $customer->id)->count());
        $this->assertSame(0, MeterReading::where('customer_id', $customer->id)->count());
    }

    public function test_a_paid_up_migrant_still_gets_a_meter_anchor(): void
    {
        $customer = $this->migrated(1160, 0);

        $this->assertSame('1160.00', $customer->openingReadingRow->current_reading);
        $this->assertSame('0.00', $customer->openingBill->due_amount);
    }

    public function test_the_opening_balance_can_be_corrected_before_it_is_used(): void
    {
        $customer = $this->migrated(4820, 1250);

        $this->assertTrue($this->opening->canAdjust($customer));

        $customer->update(['opening_reading' => 4800, 'opening_due' => 900]);
        $this->opening->adjust($customer);

        $this->assertSame(1, Bill::where('customer_id', $customer->id)->count());
        $this->assertSame('900.00', $customer->refresh()->openingBill->due_amount);
        $this->assertSame('4800.00', $customer->openingReadingRow->current_reading);
    }

    public function test_it_locks_once_a_reading_has_been_taken_on_top_of_it(): void
    {
        $customer = $this->migrated();

        MeterReading::create([
            'customer_id'      => $customer->id,
            'previous_reading' => 4820,
            'current_reading'  => 4920,
            'consumed_units'   => 100,
            'reading_date'     => '2026-09-30',
            'status'           => MeterReading::STATUS_PENDING,
            'source'           => MeterReading::SOURCE_MANUAL,
        ]);

        $this->assertFalse($this->opening->canAdjust($customer));
        $this->assertStringContainsString('meter reading', $this->opening->blockedReason($customer));
    }

    /**
     * A migrated customer can be collected from straight away — the opening bill
     * is what the collector takes money against before any month is billed.
     */
    public function test_the_opening_debt_can_be_collected_before_any_bill_is_raised(): void
    {
        $customer = $this->migrated(4820, 1250);

        $this->actingAs($this->collector())
            ->post(route('payments.store', $customer), [
                'amount'       => 1250,
                'payment_date' => '2026-09-05',
                'method'       => 'cash',
            ])->assertRedirect();

        $bill = $customer->refresh()->openingBill;
        $this->assertSame('0.00', $bill->due_amount);
        $this->assertTrue($bill->isPaid());
    }

    /**
     * Once a real bill has carried the debt forward, paying that bill must clear
     * the opening entry too — otherwise the debt is counted twice on the due list.
     */
    public function test_paying_the_carried_bill_clears_the_opening_entry(): void
    {
        $customer = $this->migrated(4820, 1250);

        $reading = MeterReading::create([
            'customer_id'      => $customer->id,
            'previous_reading' => 4820,
            'current_reading'  => 4920,
            'consumed_units'   => 100,
            'reading_date'     => '2026-09-30',
            'status'           => MeterReading::STATUS_PENDING,
            'source'           => MeterReading::SOURCE_MANUAL,
        ]);
        $bill = app(BillGenerator::class)->generateForReading($reading);

        $this->actingAs($this->collector())
            ->post(route('payments.store', $customer), [
                'amount'       => 2250,
                'payment_date' => '2026-10-05',
                'method'       => 'cash',
            ])->assertRedirect();

        $this->assertSame('0.00', $bill->refresh()->due_amount);
        $this->assertSame('0.00', $customer->refresh()->openingBill->due_amount);
    }

    protected function collector(): User
    {
        return User::create([
            'name'      => 'Collector',
            'email'     => 'collector@example.com',
            'password'  => bcrypt('secret'),
            'user_type' => 'collector',
            'status'    => User::STATUS_ACTIVE,
        ]);
    }

    public function test_it_locks_once_money_has_been_collected_against_it(): void
    {
        $customer = $this->migrated();

        $customer->openingBill->update(['paid_amount' => 500, 'due_amount' => 750]);

        $this->assertFalse($this->opening->canAdjust($customer->refresh()));
        $this->assertStringContainsString('payment', $this->opening->blockedReason($customer));
    }
}
