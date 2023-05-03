<?php

namespace App\Domain\Authentication\Commands;

use Illuminate\Console\Command;

class CreatePersonalAccessClientCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:create-personal-access-client';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Passport Personal Access Client if not exists';

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
        try {
            app('passport.client.repository')->personalAccessClient();

            return $this->warn('Personal Access Token already exists.');
        } catch (\Exception $e) {
            $this->call('passport:install', [
                '--force' => true,
            ]);
        }
    }
}
