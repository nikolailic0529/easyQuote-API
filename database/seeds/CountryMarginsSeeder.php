<?php

use App\Models \ {
    User,
    Vendor,
    Data\Country,
    Quote\Margin\CountryMargin
};
use Illuminate\Database\Seeder;

class CountryMarginsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Empty the template_fields table
        Schema::disableForeignKeyConstraints();

        DB::table('country_margins')->delete();

        Schema::enableForeignKeyConstraints();

        $countryMargins = json_decode(file_get_contents(__DIR__ . '/models/country_margins.json'), true);

        $this->command->info('Seeding Country Margins...');

        collect($countryMargins)->each(function ($margin) {
            $id = (string) Uuid::generate(4);
            $vendor_id = Vendor::where('short_code', $margin['vendor'])->first()->id;
            $value = $margin['value'];
            $is_fixed = $margin['is_fixed'];
            $method = $margin['method'];
            $quote_type = $margin['quote_type'];
            $countries = Country::whereIn('iso_3166_2', $margin['countries'])->get();

            $margin = compact('vendor_id', 'is_fixed', 'method', 'quote_type', 'value');

            User::all()->each(function ($user) use ($countries, $margin) {
                $user_id = $user->id;

                $countries->each(function ($country) use ($margin, $user_id) {
                    $country_id = $country->id;

                    CountryMargin::create(array_merge($margin, compact('country_id', 'user_id')));
                    $this->command->getOutput()->write('.');
                });
            });
        });
        $this->command->line("\n");
    }
}
