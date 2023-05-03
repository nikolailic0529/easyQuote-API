<?php

namespace App\Domain\Pipeliner\Commands;

use App\Domain\Pipeliner\Services\UnlinkEntityService;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;

class UnlinkPlEntitiesCommand extends Command
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
     */
    public function handle(UnlinkEntityService $service, LogManager $logManager): int
    {
        $service
            ->setLogger($logManager->stack(['stdout']))
            ->unlink();

        return self::SUCCESS;
    }
}
