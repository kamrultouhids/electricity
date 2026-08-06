<?php

namespace App\Console\Commands;

use App\Services\BillGenerator;
use Illuminate\Console\Command;

class GenerateBills extends Command
{
    /**
     * @var string
     */
    protected $signature = 'bills:generate {month : Billing month as YYYY-MM} {--sheet= : Restrict to a sheet id}';

    /**
     * @var string
     */
    protected $description = 'Generate monthly bills for active customers with a reading in the given month';

    public function handle(BillGenerator $generator): int
    {
        $month = $this->argument('month');

        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $this->error('Month must be in YYYY-MM format.');

            return self::FAILURE;
        }

        $result = $generator->generateForMonth($month, $this->option('sheet') ? (int) $this->option('sheet') : null);

        $this->info("Bills generated: {$result['generated']}, skipped: {$result['skipped']}.");

        return self::SUCCESS;
    }
}
