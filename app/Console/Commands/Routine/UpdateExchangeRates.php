<?php

namespace App\Console\Commands\Routine;

use App\Contracts\Services\ExchangeRateServiceInterface as Service;
use Illuminate\Console\Command;

class UpdateExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:update-exchange-rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Exchange Rates';

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
    public function handle(Service $service)
    {
        $updated = $service->updateRates();

        if ($updated) {
            return $this->info('Exchange Rates were successfully updated!');
        }

        return $this->error('Something went wrong when Exchange Rates updating.');
    }
}
