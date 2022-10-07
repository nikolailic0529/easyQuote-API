<?php

namespace App\Console\Commands\Opportunity;

use App\Services\Opportunity\MergeOpportunityService;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;

class MergeOpportunitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:merge-opportunities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge duplicated opportunities';

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
     * @param  MergeOpportunityService  $service
     * @param  LogManager  $logManager
     * @return int
     */
    public function handle(
        MergeOpportunityService $service,
        LogManager $logManager
    ): int {
        $service
            ->setLogger($logManager->stack(['stdout', 'opportunities']))
            ->work();

        return self::SUCCESS;
    }
}
