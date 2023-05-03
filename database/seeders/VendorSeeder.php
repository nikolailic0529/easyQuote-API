<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function run()
    {
        $vendors = yaml_parse_file(__DIR__.'/models/vendors.yaml');

        $connection = $this->container['db.connection'];

        $vendors = array_map(function (array $vendor) use ($connection) {
            $countryModelKeys = $connection->table('countries')
                ->whereIn('iso_3166_2', $vendor['countries'])
                ->pluck('id')
                ->all();

            return array_merge($vendor, [
                'country_model_keys' => $countryModelKeys,
            ]);
        }, $vendors);

        $countries = $connection->table('countries')
            ->whereNull('deleted_at')
            ->pluck('id');

        $connection->transaction(function () use ($countries, $connection, $vendors) {
            foreach ($vendors as $vendor) {
                $connection->table('vendors')
                    ->insertOrIgnore([
                        'id' => $vendor['id'],
                        'name' => $vendor['name'],
                        'short_code' => $vendor['short_code'],
                        'is_system' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'activated_at' => now(),
                    ]);

                foreach ($countries as $countryModelKey) {
                    $connection->table('country_vendor')
                        ->insertOrIgnore([
                            'vendor_id' => $vendor['id'],
                            'country_id' => $countryModelKey,
                        ]);
                }
            }
        });
    }
}
