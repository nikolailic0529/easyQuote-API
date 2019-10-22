<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models \ {
    Role,
    Permission
};

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
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
            $name = $attributes['name'];
            $is_system = true;
            $privilege = collect(__('role.privileges'))->last();
            $privileges = collect(__('role.modules'))->keys()->map(function ($module) use ($privilege) {
                return compact('module', 'privilege');
            })->toArray();

            $role = Role::create(compact('name', 'privileges', 'is_system'));

            $permissions = collect($attributes['permissions'])->map(function ($name) {
                return Permission::where('name', $name)->firstOrCreate(compact('name'));
            });

            $role->syncPermissions($permissions)->save();
        });
    }
}
