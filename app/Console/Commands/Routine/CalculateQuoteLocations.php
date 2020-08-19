<?php

namespace App\Console\Commands\Routine;

use App\Contracts\Services\Stats;
use App\Services\StatsAggregator;
use Illuminate\Console\Command;
use Throwable;

class CalculateQuoteLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:calculate-quote-locations {--clear-cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate quote location totals';

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
        $this->warn(QLTC_S_01);
        customlog(['message' => QLTC_S_01]);

        try {
            $service->calculateQuoteLocationTotals($this->output->createProgressBar());

            if ($this->option('clear-cache')) {
                $aggregator->flushSummaryCache();
                $this->info("\nSummary cache has been cleared!");
            }
        } catch (Throwable $e) {
            $this->error(QLTC_ERR_01);
            customlog(['ErrorCode' => 'QLTC_ERR_01'], customlog()->formatError(QLTC_ERR_01, $e));

            return false;
        }

        $this->info("\n".QLTC_F_01);
        customlog(['message' => QLTC_F_01]);

        return true;
    }
}
