<?php

namespace App\Domain\Maintenance\Commands;

use App\Domain\Maintenance\Jobs\StopMaintenance;
use Illuminate\Console\Command;

class MaintenanceStopCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:maintenance-stop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bring the application out of maintenance mode';

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
    public function handle()
    {
        StopMaintenance::dispatchNow();

        $this->info('Maintenance was completed.');
    }
}
