<?php

namespace App\Domain\Authorization\Commands;

use App\Domain\Authorization\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

class UpdateRolesCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'eq:update-roles';

    /**
     * @var string
     */
    protected $description = 'Update system defined roles';

    /**
     * Execute the console command.
     *
     * @throws \Throwable
     */
    public function handle(): int
    {
        $this->output->title('Updating System Defined Roles...');

        activity()->disableLogging();

        $this->call('db:seed', [
            '--class' => 'PermissionSeeder',
            '--force' => true,
            '--quiet' => true,
        ]);

        $this->performUpdateOfSystemRoles();

        activity()->enableLogging();

        $this->output->newLine(2);
        $this->output->success('System Defined Roles were updated!');

        return self::SUCCESS;
    }

    protected function performUpdateOfSystemRoles(): void
    {
        /** @var array $roles */
        $roles = require database_path('seeders/models/roles.php');

        /** @var ConnectionInterface $connection */
        $connection = $this->laravel[ConnectionInterface::class];

        $this->withProgressBar($roles, static function (array $attributes) use ($connection): void {
            $role = Role::query()
                ->where('name', $attributes['name'])
                ->where('is_system', true)
                ->first();

            $role ??= new Role();

            $role->name = $attributes['name'];
            $role->is_system = true;

            if (isset($attributes['access'])) {
                $role->access = $attributes['access'];
            }

            $connection->transaction(static function () use ($role, $attributes): void {
                $role->save();
                $role->syncPermissions($attributes['permissions']);
            });
        });
    }
}
