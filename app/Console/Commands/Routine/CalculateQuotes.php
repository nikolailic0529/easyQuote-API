<?php

namespace App\Console\Commands\Routine;

use App\Services\StatsAggregator;
use App\Services\StatsService;
use Illuminate\Console\Command;

class CalculateQuotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:calculate-quotes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate quotes totals';

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
    public function handle(StatsService $service, StatsAggregator $aggregator)
    {
        $service->calculateQuotesTotals();
        $aggregator->flushSummaryCache();
    }
}
