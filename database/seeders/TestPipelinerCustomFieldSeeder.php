<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestPipelinerCustomFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seeds = yaml_parse_file(database_path('seeders/models/test_pipeliner_custom_fields.yaml'));

        DB::transaction(static function () use ($seeds): void {
            foreach ($seeds as $seed) {
                DB::table('pipeliner_custom_fields')
                    ->insertOrIgnore(['created_at' => now(), 'updated_at' => now()] + $seed);
            }
        });
    }
}
