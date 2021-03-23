<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models \ {
    Role,
    Permission
};

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        //Empty the roles table
        Schema::disableForeignKeyConstraints();

        $tableNames = config('permission.table_names');

        DB::table($tableNames['role_has_permissions'])->delete();
        DB::table($tableNames['model_has_roles'])->delete();
        DB::table($tableNames['model_has_permissions'])->delete();
        DB::table($tableNames['roles'])->delete();
        DB::table($tableNames['permissions'])->delete();

        Schema::enableForeignKeyConstraints();

        $roles = json_decode(file_get_contents(__DIR__ . '/models/roles.json'), true);

        collect($roles)->each(function ($attributes) {
            /** @var Role $role */
            $role = Role::create([
                'name' => $attributes['name'],
                'is_system' => true,
            ]);

            $permissions = collect($attributes['permissions'])->map(function ($name) {
                return Permission::query()->where('name', $name)->firstOrCreate(compact('name'));
            });

            $role->syncPermissions($permissions)->save();
        });
    }
}
