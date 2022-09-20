<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seeds = collect(yaml_parse_file(__DIR__.'/models/company_categories.yaml'))
            ->map(static function (array $seed, int $i): array {
                $seed['entity_order'] = $i;
                $seed['created_at'] = now();
                $seed['updated_at'] = now();
                return $seed;
            });

        DB::transaction(static function () use ($seeds) {
            foreach ($seeds as $seed) {
                DB::table('company_categories')
                    ->insertOrIgnore($seed);
            }
        });
    }
}
