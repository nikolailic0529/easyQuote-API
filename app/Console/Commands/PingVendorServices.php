<?php

namespace App\Console\Commands;

use App\Services\VendorServices\PingVendorServicesService;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;

class PingVendorServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:ping-vs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ping VendorServices API';

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
     * @return int
     */
    public function handle(PingVendorServicesService $service, LogManager $logManager): int
    {
        $logger = $logManager->stack([
            'stdout', 'vendor-services'
        ]);

        $result = $service
            ->setLogger($logger)
            ->ping();

        if ($result) {
            return self::SUCCESS;
        }

        return self::FAILURE;
    }
}
