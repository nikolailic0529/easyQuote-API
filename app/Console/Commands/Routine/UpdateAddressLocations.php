<?php

namespace App\Console\Commands\Routine;

use App\Contracts\Services\LocationService;
use Illuminate\Console\Command;
use Throwable;

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
     * @return mixed
     */
    public function handle(LocationService $service)
    {
        $this->warn(ADDR_LCU_S_01);
        report_logger(['message' => ADDR_LCU_S_01]);

        try {
            $service->updateAddressLocations($this->output->createProgressBar());
        } catch (Throwable $e) {
            $this->error(ADDR_LCU_ERR_01);
            report_logger(['ErrorCode' => 'QTC_ERR_01'], report_logger()->formatError(ADDR_LCU_ERR_01, $e));

            return false;
        }

        $this->info("\n".ADDR_LCU_F_01);
        report_logger(['message' => ADDR_LCU_F_01]);

        return true;
    }
}
