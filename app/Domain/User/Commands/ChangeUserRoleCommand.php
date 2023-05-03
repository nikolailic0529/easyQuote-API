<?php

namespace App\Domain\User\Commands;

use Illuminate\Console\Command;

class ChangeUserRoleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:change-user-role';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change a role for the specified user';

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

        $role = $this->ask('Enter a role name');

        try {
            $user->syncRoles($role);
            $this->info("A role for user '{$user->email}' was successfully changed to '{$role}'.");
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
        }
    }
}
