<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DateMonthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seeds = yaml_parse_file(database_path('seeders/models/date_months.yaml'));

        foreach ($seeds as $seed) {
            DB::table('date_months')
                ->insertOrIgnore($seed);
        }
    }
}
