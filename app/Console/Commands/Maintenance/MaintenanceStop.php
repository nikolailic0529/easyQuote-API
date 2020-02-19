<?php

namespace App\Console\Commands\Maintenance;

use Illuminate\Console\Command;
use App\Jobs\StopMaintenance;

class MaintenanceStop extends Command
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
        StopMaintenance::dispatch();

        $this->info('Maintenance was completed.');
    }
}
