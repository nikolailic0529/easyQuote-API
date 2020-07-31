<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{
    Company,
    Role,
    Permission
};
use Illuminate\Support\Facades\DB;

class RolesUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:roles-update';

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

        activity()->disableLogging();

        DB::transaction(fn () => $this->updateSystemRoles());

        activity()->enableLogging();

        $this->info("\nSystem Defined Roles were updated!");
    }

    protected function updateSystemRoles()
    {
        $roles = json_decode(file_get_contents(database_path('seeds/models/roles.json')), true);

        collect($roles)->each(function ($attributes) {
            /** @var Role */
            $role = Role::whereName($attributes['name'])->firstOrFail();

            $privileges = collect($attributes['privileges'])->transform(fn ($privilege, $module) => compact('module', 'privilege'));

            $role->fill(compact('privileges'))->save();

            $permissions = collect($attributes['permissions'])->map(function ($name) {
                $this->output->write('.');

                return Permission::where('name', $name)->firstOrCreate(compact('name'));
            });

            $role->syncPermissions($permissions)->save();

            $companies = Company::whereIn('short_code', $attributes['companies'])->pluck('id');

            $role->companies()->sync($companies);
        });
    }
}
