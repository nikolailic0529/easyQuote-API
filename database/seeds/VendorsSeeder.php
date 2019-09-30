<?php

use Illuminate\Database\Seeder;
use App\Models \ {
    Data\Country,
    Vendor
};

class VendorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Empty the vendors table
        Schema::disableForeignKeyConstraints();

        DB::table('vendors')->delete();

        Schema::enableForeignKeyConstraints();

        $vendors = json_decode(file_get_contents(__DIR__ . '/models/vendors.json'), true);

        collect($vendors)->each(function ($vendor) {
            $vendorId = (string) Uuid::generate(4);

            DB::table('vendors')->insert([
                'id' => $vendorId,
                'name' => $vendor['name'],
                'short_code' => $vendor['short_code'],
                'is_system' => true,
                'activated_at' => now()->toDateTimeString()
            ]);

            collect($vendor['countries'])->each(function ($countryIso) use ($vendorId) {
                $country = Country::where('iso_3166_2', $countryIso)->first();

                Vendor::whereId($vendorId)->first()->countries()->attach($country);
            });
        });
    }
}
