<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\Sheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CustomerOpeningBalanceImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sheet::create(['name' => 'Sheet 1']);
    }

    protected function operator(): User
    {
        return User::create([
            'name'      => 'Operator',
            'email'     => 'operator@example.com',
            'password'  => bcrypt('secret'),
            'user_type' => 'operator',
            'status'    => User::STATUS_ACTIVE,
        ]);
    }

    protected function formPayload(array $overrides = []): array
    {
        return $overrides + [
            'sheet_id'        => 1,
            'serial_no'       => '2001',
            'name'            => 'Kamrul Islam',
            'mobile_number'   => '01710000001',
            'address'         => 'Jaforabad',
            'meter_number'    => 'MTR-2001',
            'connection_type' => 'residential',
            'connection_date' => '2025-01-10',
            'status'          => 1,
        ];
    }

    public function test_the_create_form_opens_the_ledger(): void
    {
        $this->actingAs($this->operator())
            ->post(route('customers.store'), $this->formPayload([
                'opening_reading' => 4820,
                'opening_due'     => 1250,
                'opening_as_of'   => '2026-08-31',
            ]))
            ->assertRedirect(route('customers.index'));

        $customer = Customer::sole();
        $this->assertSame('4820.00', $customer->openingReadingRow->current_reading);
        $this->assertSame('1250.00', $customer->openingBill->due_amount);
    }

    public function test_a_partly_filled_opening_balance_is_rejected(): void
    {
        $this->actingAs($this->operator())
            ->post(route('customers.store'), $this->formPayload([
                'opening_as_of' => '2026-08-31',
            ]))
            ->assertSessionHasErrors(['opening_reading', 'opening_due']);

        $this->assertSame(0, Customer::count());
    }

    public function test_an_opening_date_before_the_connection_is_rejected(): void
    {
        $this->actingAs($this->operator())
            ->post(route('customers.store'), $this->formPayload([
                'opening_reading' => 4820,
                'opening_due'     => 1250,
                'opening_as_of'   => '2024-01-01',
            ]))
            ->assertSessionHasErrors('opening_as_of');
    }

    public function test_the_csv_import_opens_the_ledger(): void
    {
        $csv = "sheet,serial_no,name,mobile_number,address,meter_number,connection_type,connection_date,status,opening_reading,opening_due,opening_as_of\n"
            . "Sheet 1,2001,Kamrul Islam,01710000001,Jaforabad,MTR-2001,residential,2025-01-10,active,4820,1250,2026-08-31\n"
            . "Sheet 1,2002,Raju,01710000002,Sitakunda,MTR-2002,residential,2025-02-15,active,,,\n";

        $this->actingAs($this->operator())
            ->post(route('customers.import'), [
                'file' => UploadedFile::fake()->createWithContent('customers.csv', $csv),
            ])
            ->assertRedirect(route('customers.index'));

        $migrated = Customer::where('serial_no', '2001')->sole();
        $this->assertSame('1250.00', $migrated->openingBill->due_amount);

        // The second row is a new connection — no opening rows at all.
        $fresh = Customer::where('serial_no', '2002')->sole();
        $this->assertNull($fresh->openingBill);
        $this->assertSame(0, MeterReading::where('customer_id', $fresh->id)->count());
    }

    /**
     * The bug the opening reading exists to prevent: the meter-reading importer
     * never takes a previous reading from the file, so without an anchor the
     * first imported reading billed the meter's entire lifetime.
     */
    public function test_an_imported_reading_bills_from_the_anchor_not_from_zero(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)->post(route('customers.store'), $this->formPayload([
            'opening_reading' => 4820,
            'opening_due'     => 0,
            'opening_as_of'   => '2026-08-31',
        ]));

        $csv = "meter_number,current_units,reading_date\nMTR-2001,4920,2026-09-30\n";

        $this->actingAs($operator)
            ->post(route('meter-readings.import'), [
                'file' => UploadedFile::fake()->createWithContent('readings.csv', $csv),
            ])
            ->assertRedirect(route('meter-readings.index'));

        $reading = MeterReading::where('source', MeterReading::SOURCE_CSV)->sole();
        $this->assertSame('4820.00', $reading->previous_reading);
        $this->assertSame('100.00', $reading->consumed_units);
    }

    public function test_a_locked_opening_balance_ignores_submitted_changes(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)->post(route('customers.store'), $this->formPayload([
            'opening_reading' => 4820,
            'opening_due'     => 1250,
            'opening_as_of'   => '2026-08-31',
        ]));

        $customer = Customer::sole();
        $customer->openingBill->update(['paid_amount' => 500, 'due_amount' => 750]);

        $this->actingAs($operator)
            ->put(route('customers.update', $customer), $this->formPayload([
                'opening_reading' => 4000,
                'opening_due'     => 99,
                'opening_as_of'   => '2026-08-31',
            ]))
            ->assertRedirect(route('customers.index'));

        $customer->refresh();
        $this->assertSame('1250.00', $customer->opening_due);
        $this->assertSame('4820.00', $customer->opening_reading);
        $this->assertSame('750.00', $customer->openingBill->due_amount);
    }

    /**
     * The form disables the opening inputs when they are locked, and a disabled
     * input submits nothing — so a locked customer always posts them blank. That
     * must not read as "the operator cleared the opening balance".
     */
    public function test_a_locked_customer_saves_when_the_opening_fields_are_absent(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)->post(route('customers.store'), $this->formPayload([
            'opening_reading' => 4820,
            'opening_due'     => 1250,
            'opening_as_of'   => '2026-08-31',
        ]));

        $customer = Customer::sole();
        $customer->openingBill->update(['paid_amount' => 500, 'due_amount' => 750]);

        // Exactly what the browser posts: no opening_* keys at all.
        $this->actingAs($operator)
            ->put(route('customers.update', $customer), $this->formPayload([
                'name' => 'Renamed Customer',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('customers.index'));

        $customer->refresh();
        $this->assertSame('Renamed Customer', $customer->name);
        $this->assertSame('1250.00', $customer->opening_due);
        $this->assertSame('4820.00', $customer->opening_reading);
        $this->assertSame('2026-08-31', $customer->opening_as_of->toDateString());
        $this->assertSame(1, Bill::where('customer_id', $customer->id)->count());
    }

    public function test_an_unrelated_edit_still_saves_while_the_opening_is_locked(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)->post(route('customers.store'), $this->formPayload([
            'opening_reading' => 4820,
            'opening_due'     => 1250,
            'opening_as_of'   => '2026-08-31',
        ]));

        $customer = Customer::sole();
        $customer->openingBill->update(['paid_amount' => 500, 'due_amount' => 750]);

        $this->actingAs($operator)
            ->put(route('customers.update', $customer), $this->formPayload([
                'name'            => 'Kamrul Islam Updated',
                'opening_reading' => 4820,
                'opening_due'     => 1250,
                'opening_as_of'   => '2026-08-31',
            ]))
            ->assertRedirect(route('customers.index'));

        $this->assertSame('Kamrul Islam Updated', $customer->refresh()->name);
        $this->assertSame(1, Bill::where('customer_id', $customer->id)->count());
    }
}
