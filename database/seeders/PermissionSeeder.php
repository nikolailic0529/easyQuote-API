<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        $seeds = json_decode(file_get_contents(database_path('seeders/models/permissions.json')), true);

        DB::transaction(function () use ($seeds) {

            foreach ($seeds as $seed) {

                Permission::query()->updateOrCreate(
                    ['name' => $seed],
                    ['guard_name' => config('auth.defaults.guard')]
                );

            }
        });
    }
}
