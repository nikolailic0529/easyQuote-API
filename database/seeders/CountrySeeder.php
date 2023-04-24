<?php

namespace Database\Seeders;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @throws \Throwable
     */
    public function run(): void
    {
        $countries = json_decode(file_get_contents(__DIR__.'/models/countries.json'), true);

        /** @var ConnectionInterface $connection */
        $connection = $this->container['db.connection'];

        $countryOrder = [
            'GB' => 0,
            'US' => 1,
            'FR' => 2,
            'CA' => 3,
            'SE' => 4,
            'NL' => 5,
            'BE' => 6,
            'AF' => 7,
            'NO' => 8,
            'DK' => 9,
            'AT' => 10,
            'ZA' => 11,
        ];

        $countries = collect($countries)
            ->map(static function (array $seed) use ($countryOrder, $connection): array {
                $defaultCurrencyId = $connection->table('currencies')
                    ->where('code', $seed['currency_code'])->value('id');

                return $seed + [
                        'default_currency_id' => $defaultCurrencyId,
                        'entity_order' => $countryOrder[$seed['iso_3166_2']] ?? null,
                    ];
            })
            ->all();

        $connection->transaction(static function () use ($connection, $countries): void {
            foreach ($countries as $country) {
                $connection->table('countries')
                    ->upsert([
                        'id' => $country['id'],
                        'capital' => $country['capital'],
                        'citizenship' => $country['citizenship'],
                        'country_code' => $country['country_code'],
                        'currency_name' => $country['currency_name'],
                        'currency_code' => $country['currency_code'],
                        'full_name' => $country['full_name'],
                        'iso_3166_2' => $country['iso_3166_2'],
                        'iso_3166_3' => $country['iso_3166_3'],
                        'name' => $country['name'],
                        'calling_code' => $country['calling_code'],
                        'default_currency_id' => $country['default_currency_id'],
                        'is_system' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'activated_at' => now(),
                        'entity_order' => $country['entity_order'],
                    ], null, [
                        'capital' => $country['capital'],
                        'citizenship' => $country['citizenship'],
                        'country_code' => $country['country_code'],
                        'currency_name' => $country['currency_name'],
                        'currency_code' => $country['currency_code'],
                        'full_name' => $country['full_name'],
                        'iso_3166_2' => $country['iso_3166_2'],
                        'iso_3166_3' => $country['iso_3166_3'],
                        'name' => $country['name'],
                        'calling_code' => $country['calling_code'],
                        'default_currency_id' => $country['default_currency_id'],
                        'entity_order' => $country['entity_order'],
                    ]);
            }
        });
    }
}
