<?php

use Illuminate\Database\Seeder;
use App\Models\Data\Country;
use Illuminate\Support\Facades\File;

class CountriesFlagsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        activity()->disableLogging();

        Country::on('mysql_unbuffered')->cursor()->each(function ($country) {
            $flag = strtolower($country->iso_3166_3) . '.svg';
            $path = public_path('img/countries/' . $flag);

            if (File::exists($path)) {
                $country->forceFill(compact('flag'))->save();
            }
        });

        activity()->enableLogging();
    }
}
