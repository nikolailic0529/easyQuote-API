<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CountryFlagSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        $connection = $this->container['db.connection'];

        $countryCodes = $connection->table('countries')->distinct('iso_3166_3')->pluck('iso_3166_3');

        $countryFlagsDirectory = public_path('img/countries/');

        foreach ($countryCodes as $alpha3Code) {
            $fileName = sprintf('%s.svg', strtolower($alpha3Code));
            $filePath = rtrim($countryFlagsDirectory, '/').'/'.$countryFlagsDirectory;

            if (file_exists($filePath)) {
                $connection->table('countries')
                    ->where('iso_3166_3', $alpha3Code)
                    ->update(['flag' => $fileName]);
            }
        }
    }
}
