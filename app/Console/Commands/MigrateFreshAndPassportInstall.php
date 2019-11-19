<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateFreshAndPassportInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fresh migrates, force install Passport, clear Cache';

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
        $this->call('migrate:fresh', [
            '--seed' => true
        ]);
        $this->call('passport:install', [
            '--force' => true
        ]);
        $this->call('search:reindex');
        $this->call('optimize:clear');
    }
}
