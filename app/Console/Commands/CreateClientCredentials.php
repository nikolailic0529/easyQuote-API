<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Arr;

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
        $this->call('config:cache');

        collect(config('auth.client_credentials'))
            ->each(function ($credentials, $service) {
                $this->createClientCredentials($credentials, $service);
            });
    }

    protected function createClientCredentials(array $credentials, string $service)
    {
        if ($this->checkClientCredentials($credentials)) {
            return $this->warn("Client Credentials for {$service} are not defined.");
        }

        $attributes = [
            'id' => $credentials['client_id'],
            'name' => $credentials['client_name'],
            'secret' => $credentials['client_secret'],
            'redirect' => url('/auth/callback'),
            'created_at' => now(),
            'updated_at' => now()
        ];

        \DB::table('oauth_clients')
            ->updateOrInsert(['id' => $attributes['id']], $attributes);

        $this->info($attributes['name'] . ' client created successfully.');

        $this->line('<comment>Client ID:</comment> ' . $attributes['id']);
        $this->line('<comment>Client secret:</comment> ' . $attributes['secret']);
    }

    protected function checkClientCredentials(array $credentials): bool
    {
        return is_null(Arr::first($credentials, function ($parameter) {
            return filled($parameter);
        }));
    }
}
