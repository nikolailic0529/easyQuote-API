<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models \ {
    Role,
    Permission
};

class RolesUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update System Defined Roles';

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
        $this->info("Updating System Defined Roles...");

        $roles = json_decode(file_get_contents(database_path('seeds/models/roles.json')), true);

        collect($roles)->each(function ($attributes) {
            $role = Role::whereName($attributes['name'])->firstOrFail();

            $permissions = collect($attributes['permissions'])->map(function ($name) {
                $this->output->write('.');
                return Permission::where('name', $name)->firstOrCreate(compact('name'));
            });

            $role->syncPermissions($permissions)->save();
        });

        $this->info("\nSystem Defined Roles were updated!");
    }
}