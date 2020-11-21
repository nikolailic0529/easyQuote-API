<?php

namespace App\Console\Commands\Routine;

use App\Services\UserActivityService;
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
    public function handle(UserActivityService $service)
    {
        $count = $service->logoutInactive();

        if ($count > 0) {
            customlog(['message' => sprintf('Logged out %s inactive users.', $count)]);
        }

        return 0;
    }
}
