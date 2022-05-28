<?php

namespace App\Console\Commands\Routine;

use Devengine\LogKeeper\Exceptions\LogUtilException;
use Devengine\LogKeeper\Services\LogKeeperService;
use Illuminate\Console\Command;

class ArchiveLogs extends Command
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
     * @param LogKeeperService $service
     * @return int
     * @throws LogUtilException
     */
    public function handle(LogKeeperService $service): int
    {
        $service->work();

        return self::SUCCESS;
    }
}
