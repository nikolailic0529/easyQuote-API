<?php

use Illuminate\Database\Seeder;

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

            DB::table('vendors')->insert([
                'id' => (string) Uuid::generate(4),
                'name' => $vendor['name'],
                'short_code' => $vendor['short_code'],
                'is_system' => true
            ]);
        });
    }
}
