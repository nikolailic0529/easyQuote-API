<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IndustrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seeds = yaml_parse_file(__DIR__.'/models/industries.yaml');

        $seeds = collect($seeds)->map(static function (array $seed): array {
            $seed['id'] = Str::orderedUuid()->toString();
            return $seed;
        });

        DB::transaction(static function () use ($seeds): void {
            foreach ($seeds as $seed) {
                DB::table('industries')->insertOrIgnore($seed);
            }
        });
    }
}
