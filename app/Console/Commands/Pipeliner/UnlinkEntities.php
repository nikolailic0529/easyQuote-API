<?php

namespace App\Console\Commands\Pipeliner;

use App\Services\Pipeliner\UnlinkEntityService;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;

class UnlinkEntities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:unlink-pl-entities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove references to Pipeliner entities';

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
     * @param UnlinkEntityService $service
     * @param LogManager $logManager
     * @return int
     */
    public function handle(UnlinkEntityService $service, LogManager $logManager): int
    {
        $service
            ->setLogger($logManager->stack(['stdout']))
            ->unlink();

        return self::SUCCESS;
    }
}
