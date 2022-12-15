<?php

namespace App\Console\Commands;

use App\Services\Address\DeduplicateAddressService;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;

class DeduplicateAddressesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:deduplicate-addresses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deduplicate the existing addresses';

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
     * @param  LogManager  $logManager
     * @param  DeduplicateAddressService  $service
     * @return int
     */
    public function handle(LogManager $logManager, DeduplicateAddressService $service): int
    {
        $logManager->setDefaultDriver('addresses');

        $logger = $logManager->stack(['addresses', 'stdout']);

        $service->setLogger($logger)
            ->work();

        return self::SUCCESS;
    }
}
