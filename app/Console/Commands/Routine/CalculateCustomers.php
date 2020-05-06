<?php

namespace App\Console\Commands\Routine;

use App\Contracts\Services\Stats;
use App\Services\StatsAggregator;
use Illuminate\Console\Command;
use Throwable;

class CalculateCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:calculate-customers {--clear-cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate customers totals';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Stats $service, StatsAggregator $aggregator)
    {
        $this->warn(CUSTC_S_01);
        report_logger(['message' => CUSTC_S_01]);

        try {
            $service->calculateCustomerTotals($this->output->createProgressBar());

            if ($this->option('clear-cache')) {
                $aggregator->flushSummaryCache();
                $this->info("\nSummary cache has been cleared!");
            }
        } catch (Throwable $e) {
            $this->error(CUSTC_ERR_01);
            report_logger(['ErrorCode' => 'QTC_ERR_01'], report_logger()->formatError(CUSTC_ERR_01, $e));

            return false;
        }

        $this->info("\n".CUSTC_F_01);
        report_logger(['message' => CUSTC_F_01]);

        return true;
    }
}
