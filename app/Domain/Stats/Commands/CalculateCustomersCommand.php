<?php

namespace App\Domain\Stats\Commands;

use App\Domain\Stats\Contracts\Stats;
use Illuminate\Console\Command;

class CalculateCustomersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:calculate-customers';

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
    public function handle(Stats $service)
    {
        $this->output->title('Customer totals calculation started');

        $service
            ->setOutput($this->getOutput())
            ->denormalizeSummaryOfCustomers();

        $this->output->success('Calculation of Customer totals successfully finished');

        return Command::SUCCESS;
    }
}
