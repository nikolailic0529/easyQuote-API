<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Company, Role};
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
        $this->output->title("Updating System Defined Roles...");

        activity()->disableLogging();

        $this->call('db:seed', [
            '--class' => 'PermissionSeeder',
            '--force' => true,
        ]);

        DB::transaction(fn () => $this->updateSystemRoles());

        activity()->enableLogging();

        $this->output->success("System Defined Roles were updated!");
    }

    protected function updateSystemRoles()
    {
        $roles = json_decode(file_get_contents(database_path('seeds/models/roles.json')), true);

        $this->output->progressStart(count($roles));

        collect($roles)->each(function ($attributes) {
            /** @var Role */
            $role = Role::query()->firstOrCreate(['name' => $attributes['name']]);

            $role->syncPermissions($attributes['permissions']);

            $companies = Company::whereIn('short_code', $attributes['companies'])->pluck('id');

            $role->companies()->sync($companies);

            $this->output->progressAdvance();
        });

        $this->output->progressFinish();
    }
}
