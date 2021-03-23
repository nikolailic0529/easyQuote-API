<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{

    /**
     * Run the database seeders.
     *
     * @return  void
     * @throws \Throwable
     */
    public function run()
    {
        $countries = json_decode(file_get_contents(__DIR__.'/models/countries.json'), true);

        $connection = $this->container['db.connection'];

        $connection->transaction(function () use ($connection, $countries) {

            foreach ($countries as $country) {

                $connection->table('countries')
                    ->insertOrIgnore([
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
                        'is_system' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'activated_at' => now()
                    ]);

            }

        });
    }
}
