<?php

namespace App\Console\Commands\Routine;

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
    public function handle()
    {
        /** Calculate quote totals. */
        $this->call(CalculateQuotes::class);

        /** Calculate opportunity totals. */
        $this->call(CalculateOpportunities::class);

        /** Calculate quote location totals. */
        $this->call(CalculateQuoteLocations::class);

        /** Calculate customer totals for each location based on quote totals. */
        $this->call(CalculateCustomers::class);

        /** Calculate existing assets. */
        $this->call(CalculateAssets::class);

        $this->info("\nSummary cache has been cleared!");

        return Command::SUCCESS;
    }
}
