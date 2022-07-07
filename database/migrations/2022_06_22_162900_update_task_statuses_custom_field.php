+<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $data = [
            "id" => "574398ac-7737-49d7-a79d-3754023bed5b",
            "field_name" => "task_statuses",
            "field_values" => [
                [
                    "id" => "e414ce7f-28ed-4d17-9ee6-c165ab350a8b",
                    "field_value" => "Not Started",
                    "is_default" => true,
                    "entity_order" => 0,
                ],
                [
                    "id" => "a119db79-34b7-40ad-a6ae-5628e0e0b2e2",
                    "field_value" => "In Progress",
                    "is_default" => false,
                    "entity_order" => 1,
                ],
                [
                    "id" => "a3fcf00d-2168-4649-9cd5-9f506c166160",
                    "field_value" => "Waiting",
                    "is_default" => false,
                    "entity_order" => 2,
                ],
                [
                    "id" => "d26383b4-a767-4944-82f1-17f9764e5e08",
                    "field_value" => "Completed",
                    "is_default" => false,
                    "entity_order" => 3,
                ],
                [
                    "id" => "e6c49ca2-5bad-43e8-b565-f7e396347b4f",
                    "field_value" => "Deferred",
                    "is_default" => false,
                    "entity_order" => 4,
                ],
            ],
        ];

        DB::transaction(static function () use ($data) {
            DB::table('custom_field_values')
                ->where('custom_field_id', $data['id'])
                ->delete();

            foreach ($data['field_values'] as $field) {
                DB::table('custom_field_values')
                    ->insert([
                        'id' => $field['id'],
                        'field_value' => $field['field_value'],
                        'is_default' => $field['is_default'],
                        'entity_order' => $field['entity_order'],
                        'custom_field_id' => $data['id'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
