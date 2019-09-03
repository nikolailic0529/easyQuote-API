<?php

use Illuminate\Database\Seeder;
use App\Models\Data \ {
    Country,
    Language
};

class CountryLanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Empty the country_language table
        Schema::disableForeignKeyConstraints();

        DB::table('country_language')->delete();

        Schema::enableForeignKeyConstraints();

        $country_language = json_decode(file_get_contents(__DIR__ . '/models/country_language.json'), true);

        collect($country_language)->each(function ($relation) {
            $country = Country::where('iso_3166_2', $relation['country'])->first();

            $languages = Language::whereIn('code', $relation['languages'])->get();
            $country->languages()->sync($languages);
        });
    }
}
