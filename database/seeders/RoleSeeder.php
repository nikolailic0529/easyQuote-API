<?php

namespace Database\Seeders;

use App\Domain\Authorization\Models\{Role};
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function run(): void
    {
        /** @var array $seeds */
        $seeds = require database_path('seeders/models/roles.php');

        /** @var ConnectionInterface $connection */
        $connection = $this->container['db.connection'];

        foreach ($seeds as $seed) {
            $role = Role::query()
                ->where('name', $seed['name'])
                ->where('is_system', true)
                ->first();

            $role ??= new Role();
            $role->name = $seed['name'];
            $role->is_system = true;
            if (isset($seed['access'])) {
                $role->access = $seed['access'];
            }

            $connection->transaction(static function () use ($role, $seed): void {
                $role->save();
                $role->syncPermissions($seed['permissions']);
            });
        }
    }
}
