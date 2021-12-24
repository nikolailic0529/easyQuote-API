<?php

namespace Database\Seeders;

use App\Models\Customer\WorldwideCustomer;
use Illuminate\Database\Seeder;

class WorldwideCustomerSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        factory(WorldwideCustomer::class, 100)->create();
    }
}
