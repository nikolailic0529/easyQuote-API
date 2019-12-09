<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateClientCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:create-client-credentials';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Special Client Credentials for S4 Service';

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
        if (blank(config('auth.s4.client_id')) || blank(config('auth.s4.client_secret') || blank(config('auth.s4.client_name')))) {
            return $this->error('The S4 Client Credentials are not set.');
        }

        $attributes = [
            'id' => config('auth.s4.client_id'),
            'name' => config('auth.s4.client_name'),
            'secret' => config('auth.s4.client_secret'),
            'redirect' => url('/auth/callback'),
            'created_at' => now(),
            'updated_at' => now()
        ];

        \DB::table('oauth_clients')->updateOrInsert(['id' => $attributes['id']], $attributes);

        $this->info('S4 client created successfully.');

        $this->line('<comment>Client ID:</comment> ' . $attributes['id']);
        $this->line('<comment>Client secret:</comment> ' . $attributes['secret']);
    }
}
