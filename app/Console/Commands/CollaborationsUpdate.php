<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CollaborationsUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:collaborations-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign Administrator Role to the Users from the list';

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
        $this->info("Reassigning Administrator role for Users...");

        activity()->disableLogging();

        $users = json_decode(file_get_contents(database_path('seeds/models/users.json')), true);

        User::whereIn('email', collect($users)->pluck('email')->toArray())
            ->nonAdministrators()
            ->get()
            ->each(function ($user) {
                $user->assignRole('Administrator');
            });

        activity()->enableLogging();

        $this->info("\nAdministrators roles for User were reassigned!");
    }

    /**
     * ReAssign Administrator Role to Users which don't have Administrator Role yet
     *
     * @return void
     */
    protected function reAssignAdministrators()
    {

    }
}
