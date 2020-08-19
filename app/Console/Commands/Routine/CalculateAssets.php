<?php

namespace App\Console\Commands\Routine;

use App\Contracts\Services\Stats;
use App\Services\StatsAggregator;
use Illuminate\Console\Command;
use Throwable;

class CalculateAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:calculate-assets {--clear-cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate asset totals';

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
        $this->warn(ASSET_TCS_01);
        customlog(['message' => ASSET_TCS_01]);

        try {
            $service->calculateAssetTotals($this->output->createProgressBar());

            if ($this->option('clear-cache')) {
                $aggregator->flushSummaryCache();
                $this->info("\nSummary cache has been cleared!");
            }
        } catch (Throwable $e) {
            $this->error(ASSET_TCERR_01);
            customlog(['ErrorCode' => 'ASSET_TCERR_01'], customlog()->formatError(ASSET_TCERR_01, $e));

            return false;
        }

        $this->info("\n".ASSET_TCF_01);
        customlog(['message' => ASSET_TCF_01]);

        return true;
    }
}
