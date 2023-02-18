<?php

namespace App\Domain\Authentication\Commands;

use Illuminate\Console\Command;

class LogoutUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:logout-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Logout a specified user';

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
        $email = $this->ask('Enter an email of the user');

        if (blank($user = app('user.repository')->findByEmail($email))) {
            return $this->warn("The system couldn't find user");
        }

        if (!$this->confirm("Is '{$user->email}' a target user?")) {
            $this->error('Please clarify the email');

            return $this->handle();
        }

        if (app('auth.service')->logout($user)) {
            return $this->info("User '{$user->email}' was successfully logged out. All user's tokens were revoked.");
        }

        $this->error('An error occured when loging out the user.');
    }
}
