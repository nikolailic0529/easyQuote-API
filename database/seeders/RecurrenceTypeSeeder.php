<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecurrenceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seeds = yaml_parse_file(database_path('seeders/models/recurrence_types.yaml'));

        foreach ($seeds as $seed) {
            DB::table('recurrence_types')
                ->insertOrIgnore($seed);
        }
    }
}
