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

        DB::transaction(
            fn () => collect($seeds)->each(fn ($permission) => Permission::query()->updateOrCreate(['name' => $permission], ['guard_name' => config('auth.defaults.guard')]))
        );
    }
}
