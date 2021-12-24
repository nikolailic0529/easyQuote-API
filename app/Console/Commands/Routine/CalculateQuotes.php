<?php

namespace App\Console\Commands\Routine;

use App\Contracts\Services\Stats;
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
     * @param Stats $service
     * @return mixed
     */
    public function handle(Stats $service)
    {
        $this->output->title('Quote totals calculation started');

        $service
            ->setOutput($this->getOutput())
            ->denormalizeSummaryOfQuotes();

        $this->output->success('Quote totals calculation successfully finished');

        return Command::SUCCESS;
    }
}
