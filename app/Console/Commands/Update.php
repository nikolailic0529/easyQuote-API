<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Update extends Command
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
        $this->call('eq:db-rearrange-timestamps');
        $this->call('eq:parser-update');
        $this->call('eq:collaborations-update');
        $this->call('eq:companies-update');
        $this->call('eq:vendors-update');
        $this->call('eq:roles-update');
        $this->call('eq:settings-sync');
        $this->call('eq:templatefields-update');
        $this->call('eq:templates-update');
        $this->call('eq:create-personal-access-client');
        $this->call('eq:create-client-credentials');
        $this->call('eq:search-reindex');
        $this->call('eq:cache-relations');
        $this->call('optimize:clear');
        $this->call('optimize');
        $this->call('route:clear');
    }
}
