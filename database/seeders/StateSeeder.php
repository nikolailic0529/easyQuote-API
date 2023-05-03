<?php

namespace Database\Seeders;

use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seeds = collect(yaml_parse_file(__DIR__.'/models/countries+states+cities.yml'));

        $seeds = $seeds->lazy()->map(static function (array $seed) {
            try {
                $country = DB::table('countries')
                    ->where('iso_3166_2', $seed['iso2'])
                    ->sole();
            } catch (RecordsNotFoundException) {
                return [];
            }

            return collect($seed['states'])
                ->map(static function (array $state) use ($country): array {
                    return [
                        'id' => Str::orderedUuid()->toString(),
                        'country_id' => $country->id,
                        'name' => $state['name'],
                        'state_code' => $state['state_code'],
                        'created_at' => now()->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString(),
                    ];
                })
                ->all();
        })
            ->collapse();

        DB::transaction(static function () use ($seeds): void {
            foreach ($seeds as $seed) {
                DB::table('states')->insertOrIgnore($seed);
            }
        });
    }
}
