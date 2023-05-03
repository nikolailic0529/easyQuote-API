<?php

use Illuminate\Database\Migrations\Migration;

class SeedDefaultPipilineAndStages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Illuminate\Support\Facades\DB::transaction(function () {
            $worldwidePipelineKey = 'e6a3a7bd-e9cb-4d0f-add7-b7cfc88768ac';

            \Illuminate\Support\Facades\DB::table('pipelines')->insert([
                'id' => $worldwidePipelineKey,
                'pipeline_name' => 'Worldwide Pipeline',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $stageOrder = 1;

            $pipelineStages = [
                [
                    'id' => '626bd0f6-dff9-4170-89d8-48492409407b',
                    'pipeline_id' => $worldwidePipelineKey,
                    'stage_name' => 'Preparation',
                    'stage_order' => $stageOrder++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],

                [
                    'id' => '6f94d649-cdb7-413f-88ca-cd1e09d2575f',
                    'pipeline_id' => $worldwidePipelineKey,
                    'stage_name' => 'Special Bid Required',
                    'stage_order' => $stageOrder++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],

                [
                    'id' => '1625b2e5-c2ef-4de3-86e4-0f7c972a840b',
                    'pipeline_id' => $worldwidePipelineKey,
                    'stage_name' => 'Quote Ready',
                    'stage_order' => $stageOrder++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],

                [
                    'id' => 'b67f1f87-d012-45ca-b896-57f21512fa78',
                    'pipeline_id' => $worldwidePipelineKey,
                    'stage_name' => 'Customer Contact',
                    'stage_order' => $stageOrder++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],

                [
                    'id' => '8ba6ed41-6a59-443f-84b8-38b3aa0f06a5',
                    'pipeline_id' => $worldwidePipelineKey,
                    'stage_name' => 'Customer Order OK',
                    'stage_order' => $stageOrder++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],

                [
                    'id' => '82df36db-76fa-462d-9960-1b19a3479ea2',
                    'pipeline_id' => $worldwidePipelineKey,
                    'stage_name' => 'Purchase Order Placed',
                    'stage_order' => $stageOrder++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],

                [
                    'id' => '63b3380c-4472-4586-950a-f9f4f67ed0ac',
                    'pipeline_id' => $worldwidePipelineKey,
                    'stage_name' => 'Processed on BC',
                    'stage_order' => $stageOrder++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],

                [
                    'id' => '8b8eb870-06c4-4ecb-aa39-063d9a02734f',
                    'pipeline_id' => $worldwidePipelineKey,
                    'stage_name' => 'Closed',
                    'stage_order' => $stageOrder++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            \Illuminate\Support\Facades\DB::table('pipeline_stages')->insert($pipelineStages);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
