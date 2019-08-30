<?php

use Illuminate\Database\Seeder;

class CurrenciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Empty the currencies table
        DB::table('currencies')->delete();

        $currencies = json_decode(file_get_contents(__DIR__ . '/models/currencies.json'), true);

        collect($currencies)->each(function ($currency) {
            DB::table('currencies')->insert([
                'id' => (string) Uuid::generate(4),
                'name' => $currency['name'],
                'code' => $currency['code'],
                'symbol' => $currency['symbol'],
            ]);
        });
    }
}
