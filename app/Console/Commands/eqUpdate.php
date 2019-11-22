<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer\Customer;

class eqUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run necessary commands after pulling the code';

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
        $this->call('migrate', [
            '--force' => true
        ]);
        $this->call('eq:parser-update');
        $this->call('eq:collaborations-update');
        $this->call('eq:companies-update');
        $this->call('eq:vendors-update');
        $this->call('eq:roles-update');
        $this->call('eq:settings-sync');
        $this->call('eq:templates-update');

        /**
         * Seeding Fake S4 Contracts if available S4 Contract count is less then 29.
         */
        Customer::notInUse()->count() < 29
            && $this->call('db:seed', [
                '--class' => 'S4ContractSeeder'
            ]);

        $this->call('eq:search-reindex');
        $this->call('optimize:clear');
    }
}
