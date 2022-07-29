<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalesUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seeds = yaml_parse_file(__DIR__.'/models/sales_units.yaml');

        $seeds = collect($seeds)
            ->map(static function (array $seed): array {
                return $seed + [
                        'created_at' => now()->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString(),
                    ];
            })
            ->all();

        DB::transaction(static function () use ($seeds): void {
            foreach ($seeds as $seed) {
                DB::table('sales_units')
                    ->insertOrIgnore($seed);
            }
        });
    }
}
