<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DateWeekSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seeds = yaml_parse_file(database_path('seeders/models/date_weeks.yaml'));

        foreach ($seeds as $seed) {
            DB::table('date_weeks')
                ->insertOrIgnore($seed);
        }
    }
}
