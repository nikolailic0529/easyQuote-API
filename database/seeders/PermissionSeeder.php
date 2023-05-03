<?php

namespace Database\Seeders;

use App\Domain\Authorization\Models\Permission;
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
        $seeds = yaml_parse_file(database_path('seeders/models/permissions.yaml'));

        DB::transaction(function () use ($seeds) {
            foreach ($seeds as $seed) {
                Permission::query()->updateOrCreate(
                    ['name' => $seed],
                    ['guard_name' => 'api']
                );
            }
        });
    }
}
