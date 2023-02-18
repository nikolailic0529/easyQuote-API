<?php

namespace App\Domain\Stats\Commands;

use Illuminate\Console\Command;

class CalculateStatsCommand extends Command
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
        /* Calculate quote totals. */
        $this->call(CalculateQuotesCommand::class);

        /* Calculate quote location totals. */
        $this->call(CalculateQuoteLocationsCommand::class);

        /* Calculate customer totals for each location based on quote totals. */
        $this->call(CalculateCustomersCommand::class);

        /* Calculate existing assets. */
        $this->call(CalculateAssetsCommand::class);

        $this->info("\nSummary cache has been cleared!");

        return Command::SUCCESS;
    }
}
