<?php

namespace App\Console\Commands;

use App\Models\{Company, Role};
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

class UpdateRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:update-roles';

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
     * @return int
     * @throws \Throwable
     */
    public function handle(): int
    {
        $this->output->title("Updating System Defined Roles...");

        activity()->disableLogging();

        $this->call('db:seed', [
            '--class' => 'PermissionSeeder',
            '--force' => true,
            '--quiet' => true,
        ]);

        $this->performUpdateOfSystemRoles();

        activity()->enableLogging();

        $this->output->newLine(2);
        $this->output->success("System Defined Roles were updated!");

        return self::SUCCESS;
    }

    protected function performUpdateOfSystemRoles(): void
    {
        /** @var array $roles */
        $roles = require database_path('seeders/models/roles.php');

        /** @var ConnectionInterface $connection */
        $connection = $this->laravel[ConnectionInterface::class];

        $this->withProgressBar($roles, function (array $attributes) use ($connection) {

            $role = Role::query()
                ->where('name', $attributes['name'])
                ->where('is_system', true)
                ->first();

            /** @var Role $role */
            $role ??= tap(new Role(), function (Role $role) use ($connection, $attributes) {
                $role->name = $attributes['name'];
                $role->is_system = true;

                $connection->transaction(fn() => $role->save());
            });

            $connection->transaction(fn() => $role->syncPermissions($attributes['permissions']));
        });
    }
}
