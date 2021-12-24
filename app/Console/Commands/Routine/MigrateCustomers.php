<?php

namespace App\Console\Commands\Routine;

use App\Contracts\Services\MigratesCustomerEntity;
use App\Contracts\WithOutput;
use App\Facades\CustomerFlow;
use Illuminate\Console\Command;
use Throwable;

class MigrateCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:migrate-customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate customers to external companies';

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
    public function handle(MigratesCustomerEntity $customerMigrateService)
    {
        if ($customerMigrateService instanceof WithOutput) {
            $customerMigrateService->setOutput($this->getOutput());
        }

        $this->getOutput()->title("Migrating Customer entities into Companies...");

        $customerMigrateService->migrateCustomers();

        $this->getOutput()->success("Migration has been finished.");

        return true;
    }
}
