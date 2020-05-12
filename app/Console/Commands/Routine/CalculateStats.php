<?php

namespace App\Console\Commands\Routine;

use App\Services\StatsAggregator;
use Illuminate\Console\Command;

class CalculateStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:calculate-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate the stats';

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
    public function handle(StatsAggregator $aggregator)
    {
        /** Calculate quote totals. */
        $this->call('eq:calculate-quotes');

        /** Calculate quote location totals. */
        $this->call('eq:calculate-quote-locations');

        /** Calculate customer totals for each location based on quote totals. */
        $this->call('eq:calculate-customers');

        /** Calculate existing assets. */
        $this->call('eq:calculate-assets');

        $aggregator->flushSummaryCache();
        
        $this->info("\nSummary cache has been cleared!");
    }
}
