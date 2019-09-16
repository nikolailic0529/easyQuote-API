<?php

use Illuminate\Database\Seeder;

class CustomersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Empty the customers table
        Schema::disableForeignKeyConstraints();

        DB::table('customers')->delete();

        Schema::enableForeignKeyConstraints();

        $customers = json_decode(file_get_contents(__DIR__ . '/models/customers.json'), true);

        collect($customers)->each(function ($customer) {
            DB::table('customers')->insert([
                'id' => (string) Uuid::generate(4),
                'name' => $customer['name'],
                'rfq' => $customer['rfq'],
                'valid_until' => now()->create($customer['valid_until'])->toDateTimeString(),
                'support_start' => now()->create($customer['support_start'])->toDateTimeString(),
                'support_end' => now()->create($customer['support_end'])->toDateTimeString(),
                'created_at' => now()->toDateTimeString()
            ]);
        });
    }
}
