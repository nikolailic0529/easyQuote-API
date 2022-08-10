<?php

use Database\Seeders\PipelineSeeder;
use Database\Seeders\SpaceSeeder;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Artisan::call(SeedCommand::class, [
            '--class' => SpaceSeeder::class,
            '--force' => true,
        ]);

        Artisan::call(SeedCommand::class, [
            '--class' => PipelineSeeder::class,
            '--force' => true,
        ]);

        $seeds = [
            [
                // name: Default Pipeline
                "id" => "e6a3a7bd-e9cb-4d0f-add7-b7cfc88768ac",
                "space_id" => "38e1d441-e57a-466f-b60d-7f314f16adc3",
                "pipeline_stages" => [
                    [
                        "id" => "626bd0f6-dff9-4170-89d8-48492409407b",
                        "stage_name" => "Preparation",
                        "stage_order" => 1,
                        "stage_percentage" => 10,
                    ],
                    [
                        "id" => "6f94d649-cdb7-413f-88ca-cd1e09d2575f",
                        "stage_name" => "Special_Bid_Required",
                        "stage_order" => 2,
                        "stage_percentage" => 15,
                    ],
                    [
                        "id" => "1625b2e5-c2ef-4de3-86e4-0f7c972a840b",
                        "stage_name" => "Quote_Ready",
                        "stage_order" => 3,
                        "stage_percentage" => 25,
                    ],
                    [
                        "id" => "b67f1f87-d012-45ca-b896-57f21512fa78",
                        "stage_name" => "Customer_Contact",
                        "stage_order" => 4,
                        "stage_percentage" => 50,
                    ],
                    [
                        "id" => "8ba6ed41-6a59-443f-84b8-38b3aa0f06a5",
                        "stage_name" => "Cust_Order_Ok",
                        "stage_order" => 5,
                        "stage_percentage" => 100,
                    ],
                    [
                        "id" => "82df36db-76fa-462d-9960-1b19a3479ea2",
                        "stage_name" => "PO_Placed",
                        "stage_order" => 6,
                        "stage_percentage" => 100,
                    ],
                    [
                        "id" => "63b3380c-4472-4586-950a-f9f4f67ed0ac",
                        "stage_name" => "Processed_in_MC",
                        "stage_order" => 7,
                        "stage_percentage" => 100,
                    ],
                    [
                        "id" => "8b8eb870-06c4-4ecb-aa39-063d9a02734f",
                        "stage_name" => "Proforma_Ordered",
                        "stage_order" => 8,
                        "stage_percentage" => 100,
                    ],
                    [
                        "id" => "219e899a-b0a0-4d25-af82-f45a3b722b2f",
                        "stage_name" => "Closed",
                        "stage_order" => 9,
                        "stage_percentage" => 100,
                    ],
                ],
            ],
            [
                // name: DEV
                "id" => "3f48d08b-2238-4938-8c78-49cd4327514e",
                "space_id" => "38e1d441-e57a-466f-b60d-7f314f16adc3",
                "pipeline_stages" => [
                    [
                        "id" => "11272564-2d73-4426-9aa1-ed6ce89cb883",
                        "stage_name" => "Preparation",
                        "stage_order" => 1,
                        "stage_percentage" => 10,
                    ],
                    [
                        "id" => "99e53aba-fc4a-4bb2-b785-dc3214b8ffc4",
                        "stage_name" => "Special_Bid_Required",
                        "stage_order" => 2,
                        "stage_percentage" => 15,
                    ],
                    [
                        "id" => "af9f6f40-546f-4529-b496-a2b0e8a7cbab",
                        "stage_name" => "Quote_Ready",
                        "stage_order" => 3,
                        "stage_percentage" => 25,
                    ],
                    [
                        "id" => "9f78c760-fd56-4cc2-9723-877d368b869d",
                        "stage_name" => "Customer_Contact",
                        "stage_order" => 4,
                        "stage_percentage" => 50,
                    ],
                    [
                        "id" => "c3379ced-66a2-47a8-8af5-337e45d975fe",
                        "stage_name" => "Cust_Order_Ok",
                        "stage_order" => 5,
                        "stage_percentage" => 100,
                    ],
                    [
                        "id" => "c7960dbe-3464-4513-a912-2cc28d9a1d2d",
                        "stage_name" => "PO_Placed",
                        "stage_order" => 6,
                        "stage_percentage" => 100,
                    ],
                    [
                        "id" => "588def19-4624-439e-9a26-59731fae9413",
                        "stage_name" => "Processed_in_MC",
                        "stage_order" => 7,
                        "stage_percentage" => 100,
                    ],
                    [
                        "id" => "073cde0b-7a3f-454d-a1b8-2e54ed6c2df3",
                        "stage_name" => "Closed",
                        "stage_order" => 8,
                        "stage_percentage" => 100,
                    ],
                    [
                        "id" => "97386f57-bcc4-49e9-98cd-45bf0727652f",
                        "stage_name" => "Proforma_Ordered",
                        "stage_order" => 9,
                        "stage_percentage" => 100,
                    ],
                ],
            ],
        ];

        DB::transaction(static function () use ($seeds): void {
            foreach ($seeds as $pipeline) {
                foreach ($pipeline['pipeline_stages'] as $stage) {
                    DB::table('pipeline_stages')
                        ->upsert([
                            'id' => $stage['id'],
                            'pipeline_id' => $pipeline['id'],
                            'stage_name' => $stage['stage_name'],
                            'stage_order' => $stage['stage_order'],
                            'stage_percentage' => $stage['stage_percentage'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ], 'id', [
                            'stage_name' => $stage['stage_name'],
                            'stage_order' => $stage['stage_order'],
                            'stage_percentage' => $stage['stage_percentage'],
                        ]);
                }
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
