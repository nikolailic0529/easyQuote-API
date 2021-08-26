<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BusinessDivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $connection = $this->container['db.connection'];

        $connection->table('business_divisions')
            ->insertOrIgnore(['id' => '45fc3384-27c1-4a44-a111-2e52b072791e', 'division_name' => 'Rescue']);

        $connection->table('business_divisions')
            ->insertOrIgnore(['id' => 'f911cb0b-a1b0-4943-91e7-0a1c796984a1', 'division_name' => 'Worldwide']);

        $connection->table('business_divisions')
            ->insertOrIgnore(['id' => 'ffbf22a6-36f6-4e82-97e9-b3877a2aac33', 'division_name' => 'Contracts']);
    }
}
