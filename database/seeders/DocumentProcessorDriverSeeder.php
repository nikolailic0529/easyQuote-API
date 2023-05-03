<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DocumentProcessorDriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seeds = json_decode(file_get_contents(database_path('seeders/models/document_processor_drivers.json')), true);

        /** @var \Illuminate\Database\ConnectionInterface $connection */
        $connection = $this->container['db.connection'];

        $connection->transaction(function () use ($seeds, $connection) {
            foreach ($seeds as $seed) {
                $connection->table('document_processor_drivers')
                    ->insertOrIgnore([
                        'id' => $seed['id'],
                        'driver_name' => $seed['driver_name'],
                    ]);
            }
        });
    }
}
