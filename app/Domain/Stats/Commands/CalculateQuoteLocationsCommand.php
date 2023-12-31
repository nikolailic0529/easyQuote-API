<?php

namespace App\Domain\Stats\Commands;

use App\Domain\Stats\Contracts\Stats;
use Illuminate\Console\Command;

class CalculateQuoteLocationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:calculate-quote-locations';

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
    public function handle(Stats $service)
    {
        $this->output->title('Quote location totals calculation started');

        $service
            ->setOutput($this->getOutput())
            ->denormalizeSummaryOfLocations();

        $this->output->success('Quote location totals calculation successfully finished');

        return Command::SUCCESS;
    }
}
