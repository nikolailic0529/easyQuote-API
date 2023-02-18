<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SalesOrderSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        factory(\App\Domain\Worldwide\Models\SalesOrder::class, 10)->create();
    }
}
