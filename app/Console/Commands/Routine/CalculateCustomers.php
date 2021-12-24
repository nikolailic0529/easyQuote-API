<?php

namespace App\Console\Commands\Routine;

use App\Contracts\Services\Stats;
use Illuminate\Console\Command;

class CalculateCustomers extends Command
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
     * @param Stats $service
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
