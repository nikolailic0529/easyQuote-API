<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Passport\Token;

class RevokeTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:revoke-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revoke all existing Personal Access Tokens';

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
        Token::query()->update(['revoked' => true]);

        $this->info('All Personal Access Tokens were revoked!');
    }
}
