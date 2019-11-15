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
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
                'activated_at' => now()->toDateTimeString()
            ]);

            $createdVendor = Vendor::whereId($vendorId)->first();
            $createdVendor->createLogo($vendor['logo'], true);

            collect($vendor['countries'])->each(function ($countryIso) use ($createdVendor) {
                $country = Country::where('iso_3166_2', $countryIso)->first();

                $createdVendor->countries()->attach($country);
            });
        });
    }
}
