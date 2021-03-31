<?php

namespace App\Console\Commands\Routine;

use App\Contracts\Services\Stats;
use App\Services\StatsAggregator;
use Illuminate\Console\Command;

class CalculateOpportunities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:calculate-opportunities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @param Stats $service
     * @return mixed
     */
    public function handle(Stats $service)
    {
        $this->output->title('Opportunity totals calculation started');

        $service
            ->setOutput($this->getOutput())
            ->denormalizeSummaryOfOpportunities();

        $this->output->success('Calculation of Opportunity Totals has been successfully finished');

        return Command::SUCCESS;
    }
}
