<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return  void
     */
    public function run()
    {
        //Empty the countries table
        DB::table(\Config::get('countries.table_name'))->delete();

        //Get all of the countries
        $countries = Countries::getList();
        collect($countries)->each(function ($country) {
            DB::table(\Config::get('countries.table_name'))->insert(array(
                'id' => (string) Uuid::generate(4),
                'capital' => ((isset($country['capital'])) ? $country['capital'] : null),
                'citizenship' => ((isset($country['citizenship'])) ? $country['citizenship'] : null),
                'country_code' => $country['country-code'],
                'full_name' => ((isset($country['full_name'])) ? $country['full_name'] : null),
                'iso_3166_2' => $country['iso_3166_2'],
                'iso_3166_3' => $country['iso_3166_3'],
                'name' => $country['name'],
                'calling_code' => $country['calling_code'],
            ));
        });
    }
}
