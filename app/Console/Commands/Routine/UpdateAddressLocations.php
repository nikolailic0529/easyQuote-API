<?php

namespace App\Console\Commands\Routine;

use App\Contracts\LoggerAware;
use App\Contracts\Services\AddressGeocoder;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;

class UpdateAddressLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:update-address-locations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update address locations [coordinates]';

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
    public function handle(AddressGeocoder $service, LogManager $logManager): int
    {
        $this->getOutput()->title("Geocoding the address locations...");

        if ($service instanceof LoggerAware) {
            $service->setLogger(
                $logManager->stack(['geocoding', 'stdout'])
            );
        }

        $service->geocodeAddressLocations();

        return self::SUCCESS;
    }
}
