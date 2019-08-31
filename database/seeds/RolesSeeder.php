<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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

        DB::table('roles')->delete();

        Schema::enableForeignKeyConstraints();

        $roles = json_decode(file_get_contents(__DIR__ . '/models/roles.json'), true);

        collect($roles)->each(function ($role) {
            DB::table('roles')->insert([
                'id' => (string) Uuid::generate(),
                'name' => $role['name'],
                'is_admin' => $role['is_admin'],
                'is_system' => true
            ]);
        });
    }
}
