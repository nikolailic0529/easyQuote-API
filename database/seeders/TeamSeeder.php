<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $connection = $this->container['db.connection'];

        $connection->transaction(function () use ($connection) {

            $connection->table('teams')->insertOrIgnore([
                'id' => UT_RESCUE,
                'team_name' => 'Rescue',
                'business_division_id' => BD_RESCUE,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $connection->table('teams')->insertOrIgnore([
                'id' => UT_EPD_WW,
                'team_name' => 'EPD Worldwide',
                'business_division_id' => BD_WORLDWIDE,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

        });

    }
}
