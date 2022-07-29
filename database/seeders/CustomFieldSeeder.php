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
        $allowedBySeeds = [];

        foreach ($seeds as $customField) {
            $customFieldSeeds[] = [
                'id' => $customField['id'],
                'pl_reference' => $customField['pl_reference'] ?? null,
                'field_name' => $customField['field_name'],
                'calc_formula' => $customField['calc_formula'] ?? null,
                'parent_field_id' => $customField['parent_field_id'] ?? null,
            ];

            foreach ($customField['field_values'] as $key => $fieldValue) {
                $customFieldValueSeeds[] = [
                    'id' => $fieldValue['id'],
                    'pl_reference' => $fieldValue['pl_reference'] ?? null,
                    'custom_field_id' => $customField['id'],
                    'field_value' => $fieldValue['field_value'],
                    'calc_value' => $fieldValue['calc_value'],
                    'is_default' => $fieldValue['is_default'],
                    'entity_order' => $key,
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString()
                ];

                foreach ($fieldValue['allowed_by'] ?? [] as $allowedById) {
                    $allowedBySeeds[] = [
                        'field_value_id' => $fieldValue['id'],
                        'allowed_by_id' => $allowedById
                    ];
                }
            }
        }

        $connection = $this->container['db.connection'];

        $connection->transaction(function () use ($customFieldValueSeeds, $customFieldSeeds, $allowedBySeeds, $connection) {
            foreach ($customFieldSeeds as $seed) {
                $connection->table('custom_fields')
                    ->insertOrIgnore($seed);
            }

            foreach ($customFieldValueSeeds as $seed) {
                $connection->table('custom_field_values')
                    ->insertOrIgnore($seed);
            }

            foreach ($allowedBySeeds as $seed) {
                $connection->table('custom_field_value_allowed_by')
                    ->insertOrIgnore($seed);
            }
        });
    }
}
