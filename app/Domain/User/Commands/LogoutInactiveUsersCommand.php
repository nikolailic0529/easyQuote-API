<?php

namespace App\Domain\User\Commands;

use App\Domain\User\Services\InactiveUserService;
use Illuminate\Console\Command;

class LogoutInactiveUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:logout-inactive-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Logout inactive users';

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
     */
    public function handle(InactiveUserService $service): int
    {
        $count = $service->logoutInactiveUsers();

        if ($count > 0) {
            customlog(['message' => sprintf('Logged out %s inactive users.', $count)]);
        }

        return self::SUCCESS;
    }
}
