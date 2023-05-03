<?php

namespace App\Domain\Log\Commands;

use Devengine\LogKeeper\Exceptions\LogUtilException;
use Devengine\LogKeeper\Services\LogKeeperService;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;

class ArchiveLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:archive-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive obsolete logs';

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
     * @throws LogUtilException
     */
    public function handle(LogKeeperService $service, LogManager $logManager): int
    {
        $service->setLogger($logManager->channel('stdout'));
        $service->work();

        return self::SUCCESS;
    }
}
