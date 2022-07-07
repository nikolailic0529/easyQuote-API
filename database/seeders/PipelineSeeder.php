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

        $seeds = collect($seeds)
            ->map(function (array $seed) {

                $form = $seed['opportunity_form'];

                $schemaPath = database_path('seeders/models/opportunity_form_schemas/'.$form['form_schema']);

                $seed['opportunity_form']['form_schema'] = json_decode(file_get_contents($schemaPath), true);

                return $seed;
            })
            ->all();

        $defaultPipelineId = value(function () use ($seeds): ?string {

            foreach ($seeds as $pipelineSeed) {
                if ($pipelineSeed['is_default']) {
                    return $pipelineSeed['id'];
                }
            }

            return null;

        });

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
                        'updated_at' => now(),
                    ]);

                $stageCount = count($pipelineSeed['pipeline_stages']);

                foreach ($pipelineSeed['pipeline_stages'] as $i => $stageSeed) {

                    $percentage = ($stageCount - ($stageCount - $i - 1)) / $stageCount * 100;

                    $connection->table('pipeline_stages')
                        ->insertOrIgnore([
                            'id' => $stageSeed['id'],
                            'pipeline_id' => $pipelineSeed['id'],
                            'stage_name' => $stageSeed['stage_name'],
                            'stage_order' => $stageSeed['stage_order'],
                            'stage_percentage' => $percentage,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                }

                $connection->table('opportunity_form_schemas')
                    ->upsert([
                        'id' => $pipelineSeed['opportunity_form']['form_schema']['id'],
                        'form_data' => json_encode($pipelineSeed['opportunity_form']['form_schema']['form_data']),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ], null, [
                        'form_data' => json_encode($pipelineSeed['opportunity_form']['form_schema']['form_data']),
                    ]);

                $connection->table('opportunity_forms')
                    ->upsert([
                        'id' => $pipelineSeed['opportunity_form']['id'],
                        'pipeline_id' => $pipelineSeed['id'],
                        'form_schema_id' => $pipelineSeed['opportunity_form']['form_schema']['id'],
                        'is_system' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ], null, [
                        'is_system' => true,
                    ]);
            }

        });

        $defaultPipelineDoesntExist = $connection->table('pipelines')
            ->whereNull('deleted_at')
            ->where('is_default', true)
            ->doesntExist();

        if ($defaultPipelineDoesntExist) {

            $connection->transaction(function () use ($connection, $defaultPipelineId) {

                $connection->table('pipelines')
                    ->where('id', $defaultPipelineId)
                    ->update(['is_default' => true]);

            });

        }
    }
}
