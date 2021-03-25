<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CustomFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seeds = json_decode(file_get_contents(database_path('seeders/models/custom_fields.json')), true);

        $customFieldSeeds = [];
        $customFieldValueSeeds = [];

        foreach ($seeds as $customField) {
            $customFieldSeeds[] = [
                'id' => $customField['id'],
                'field_name' => $customField['field_name'],
            ];

            foreach ($customField['field_values'] as $key => $fieldValue) {
                $customFieldValueSeeds[] = [
                    'id' => $fieldValue['id'],
                    'custom_field_id' => $customField['id'],
                    'field_value' => $fieldValue['field_value'],
                    'is_default' => $fieldValue['is_default'],
                    'entity_order' => $key,
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString()
                ];
            }
        }

        $connection = $this->container['db.connection'];

        $connection->transaction(function () use ($customFieldValueSeeds, $customFieldSeeds, $connection) {
            foreach ($customFieldSeeds as $seed) {
                $connection->table('custom_fields')
                    ->insertOrIgnore($seed);
            }

            foreach ($customFieldValueSeeds as $seed) {
                $connection->table('custom_field_values')
                    ->insertOrIgnore($seed);
            }
        });
    }
}
