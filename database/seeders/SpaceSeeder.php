<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SpaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seeds = json_decode(file_get_contents(database_path('seeders/models/spaces.json')), true);

        /** @var \Illuminate\Database\ConnectionInterface $connection */
        $connection = $this->container['db.connection'];

        $connection->transaction(function () use ($seeds, $connection) {

            foreach ($seeds as $seed) {

                $connection->table('spaces')
                    ->upsert([
                        'id' => $seed['id'],
                        'space_name' => $seed['space_name']
                    ], null, [
                        'space_name' => $seed['space_name']
                    ]);
                
            }

        });
    }
}
