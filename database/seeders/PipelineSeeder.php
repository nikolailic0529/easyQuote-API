<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PipelineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seeds = json_decode(file_get_contents(database_path('seeders/models/pipelines.json')), true);

        /** @var \Illuminate\Database\ConnectionInterface $connection */
        $connection = $this->container['db.connection'];

        $connection->transaction(function () use ($connection, $seeds) {

            foreach ($seeds as $pipelineSeed) {

                $connection->table('pipelines')
                    ->insertOrIgnore([
                        'id' => $pipelineSeed['id'],
                        'space_id' => $pipelineSeed['space_id'],
                        'pipeline_name' => $pipelineSeed['pipeline_name'],
                        'is_system' => true,
                        'is_default' => $pipelineSeed['is_default'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                foreach ($pipelineSeed['pipeline_stages'] as $stageSeed) {

                    $connection->table('pipeline_stages')
                        ->insertOrIgnore([
                            'id' => $stageSeed['id'],
                            'pipeline_id' => $pipelineSeed['id'],
                            'stage_name' => $stageSeed['stage_name'],
                            'stage_order' => $stageSeed['stage_order'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                }

                $connection->table('opportunity_form_schemas')
                    ->upsert([
                        'id' => $pipelineSeed['opportunity_form']['form_schema']['id'],
                        'form_data' => json_encode($pipelineSeed['opportunity_form']['form_schema']['form_data']),
                        'created_at' => now(),
                        'updated_at' => now()
                    ], null, [
                        'form_data' => json_encode($pipelineSeed['opportunity_form']['form_schema']['form_data']),
                    ]);

                $connection->table('opportunity_forms')
                    ->insertOrIgnore([
                        'id' => $pipelineSeed['opportunity_form']['id'],
                        'pipeline_id' => $pipelineSeed['id'],
                        'form_schema_id' => $pipelineSeed['opportunity_form']['form_schema']['id'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

            }

        });
    }
}
