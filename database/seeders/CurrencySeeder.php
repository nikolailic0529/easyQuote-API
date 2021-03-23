<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     * @throws \Throwable
     */
    public function run()
    {
        $currencies = json_decode(file_get_contents(__DIR__.'/models/currencies.json'), true);

        $connection = $this->container['db.connection'];

        $connection->transaction(function () use ($connection, $currencies) {

            foreach ($currencies as $currency) {

                $connection->table('currencies')
                    ->insertOrIgnore([
                        'id' => $currency['id'],
                        'name' => $currency['name'],
                        'code' => $currency['code'],
                        'symbol' => $currency['symbol']
                    ]);

            }

        });
    }
}
