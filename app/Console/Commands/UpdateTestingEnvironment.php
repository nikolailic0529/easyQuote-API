<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\{
    Facades\DB, Facades\Schema
};

class UpdateTestingEnvironment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:update-testing-env';

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
        $truncatableTables = ['quote_files', 'imported_rows'];

        if (app()->environment('testing')) {
            Schema::disableForeignKeyConstraints();
            collect($truncatableTables)->each(fn ($table) => DB::table($table)->truncate());
            Schema::enableForeignKeyConstraints();
        }

        $this->call('migrate', [
            '--force' => true
        ]);

        $this->call('eq:db-rearrange-timestamps');
        $this->call('eq:parser-update');
        $this->call('eq:collaborations-update');
        $this->call('eq:companies-update');
        $this->call('eq:vendors-update');
        $this->call('eq:roles-update');
        $this->call('eq:settings-sync');
        $this->call('eq:update-exchange-rates');
        $this->call('eq:templatefields-update');
        $this->call('eq:templates-update');
        $this->call('eq:update-templates-assets');
        $this->call('eq:currencies-update');
        $this->call('eq:countries-update');
        $this->call('eq:create-personal-access-client');
        $this->call('eq:create-client-credentials');
        $this->call('eq:search-reindex');
        $this->call('cache:clear');
        $this->call('optimize');
    }
}