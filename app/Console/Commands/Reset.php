<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Reset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe Database, fresh Migrates, force install Passport, clear Cache';

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
        $this->call('eq:search-reindex');
        $this->call('optimize:clear');
    }
}
