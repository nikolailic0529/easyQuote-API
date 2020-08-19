<?php

namespace App\Console\Commands\Routine;

use App\Contracts\Repositories\UserRepositoryInterface as Users;
use Illuminate\Console\Command;

class LogoutInactiveUsers extends Command
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
     *
     * @return mixed
     */
    public function handle(Users $users)
    {
        $time = now()->subMinutes(config('activity.expires_in', 60));

        $affected = $users->updateWhere(
            ['already_logged_in' => false],
            [['last_activity_at', '<=', $time], ['already_logged_in', '=', true]]
        );

        customlog(['message' => sprintf('Logged out %s inactive users.', $affected)]);

        return 0;
    }
}
