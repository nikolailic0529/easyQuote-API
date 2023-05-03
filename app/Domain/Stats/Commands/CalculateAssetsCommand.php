<?php

namespace App\Domain\Stats\Commands;

use App\Domain\Stats\Contracts\Stats;
use Illuminate\Console\Command;

class CalculateAssetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:calculate-assets';

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
    public function handle(Stats $service)
    {
        $this->output->title('Asset totals calculation started');

        $service
            ->setOutput($this->getOutput())
            ->denormalizeSummaryOfAssets();

        $this->output->success('Calculation of Asset totals has benn successfully finished');

        return Command::SUCCESS;
    }
}
