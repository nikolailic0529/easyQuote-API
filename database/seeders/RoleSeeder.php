<?php

namespace Database\Seeders;

use App\Models\{Company, Role};
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     * @throws \Throwable
     */
    public function run()
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

            /** @var Role $role */
            $role ??= tap(new Role(), function (Role $role) use ($connection, $seed) {
                $role->name = $seed['name'];
                $role->is_system = true;

                $connection->transaction(fn() => $role->save());
            });

            $connection->transaction(fn() => $role->syncPermissions($seed['permissions']));
        }
    }
}
