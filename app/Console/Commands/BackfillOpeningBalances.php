<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\CustomerOpeningBalance;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Opening balances for customers who were already entered in the system before
 * opening balances existed — their meter is unanchored and their old debt is
 * missing. New customers get theirs on the create form instead.
 */
class BackfillOpeningBalances extends Command
{
    /**
     * @var string
     */
    protected $signature = 'customers:backfill-opening
        {file : CSV with serial_no (or meter_number), opening_reading, opening_due, opening_as_of}
        {--dry-run : Report what would change without writing anything}';

    /**
     * @var string
     */
    protected $description = 'Backfill opening meter readings and outstanding dues for existing customers';

    public function handle(CustomerOpeningBalance $opening): int
    {
        $path = $this->argument('file');

        if (! is_readable($path)) {
            $this->error("Cannot read {$path}.");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);
            $this->error('The CSV file is empty.');

            return self::FAILURE;
        }

        // Strip the UTF-8 BOM (Excel adds it) so the first header key is usable.
        $header = array_map(fn ($h) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $h))), $header);

        $dryRun = (bool) $this->option('dry-run');
        $applied = 0;
        $skipped = 0;
        $line = 1; // header is line 1

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $data = [];
            foreach ($header as $i => $key) {
                $value = isset($row[$i]) ? trim((string) $row[$i]) : '';
                $data[$key] = $value === '' ? null : $value;
            }

            $customer = $this->resolve($data);

            if (! $customer) {
                $this->warn("Row {$line}: no customer matched.");
                $skipped++;
                continue;
            }

            // Anyone already billed or collected from has a ledger that a
            // backfill would rewrite underneath them — leave those alone and
            // report them, rather than quietly corrupting their history.
            if ($blocked = $opening->blockedReason($customer)) {
                $this->warn("Row {$line}: {$customer->name} ({$customer->serial_no}) skipped — {$blocked}");
                $skipped++;
                continue;
            }

            if (! $data['opening_as_of'] || $data['opening_reading'] === null || $data['opening_due'] === null) {
                $this->warn("Row {$line}: opening_reading, opening_due and opening_as_of are all required.");
                $skipped++;
                continue;
            }

            try {
                $asOf = Carbon::parse($data['opening_as_of'])->toDateString();
            } catch (\Throwable $e) {
                $this->warn("Row {$line}: could not read opening_as_of \"{$data['opening_as_of']}\".");
                $skipped++;
                continue;
            }

            $this->line(sprintf(
                '%s %s (%s): reading %s, due %s as of %s',
                $dryRun ? 'Would set' : 'Set',
                $customer->name,
                $customer->serial_no,
                $data['opening_reading'],
                $data['opening_due'],
                $asOf,
            ));

            if (! $dryRun) {
                $customer->update([
                    'opening_reading' => $data['opening_reading'],
                    'opening_due'     => $data['opening_due'],
                    'opening_as_of'   => $asOf,
                ]);

                // adjust() rather than materialize(): a partial earlier run may
                // have left rows behind, and this replaces them cleanly.
                $opening->adjust($customer->refresh());
            }

            $applied++;
        }

        fclose($handle);

        $verb = $dryRun ? 'Would apply' : 'Applied';
        $this->info("{$verb} {$applied} opening balance(s), skipped {$skipped}.");

        return self::SUCCESS;
    }

    /**
     * Match a row to a customer by serial number, falling back to meter number.
     * An ambiguous meter number is refused rather than guessed — meter numbers
     * are not unique in the schema.
     */
    protected function resolve(array $data): ?Customer
    {
        if ($serial = $data['serial_no'] ?? null) {
            return Customer::where('serial_no', $serial)->first();
        }

        if ($meter = $data['meter_number'] ?? null) {
            $matches = Customer::where('meter_number', $meter)->get();

            return $matches->count() === 1 ? $matches->first() : null;
        }

        return null;
    }
}
