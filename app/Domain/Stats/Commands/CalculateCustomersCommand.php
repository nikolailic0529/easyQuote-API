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

    public function handle(Stats $service): int
    {
        $this->output->title('Customer totals calculation started');

        $service
            ->setOutput($this->getOutput())
            ->denormalizeSummaryOfCustomers();

        $this->output->success('Calculation of Customer totals successfully finished');

        return self::SUCCESS;
    }
}
