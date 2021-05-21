<?php

namespace App\Services\Pipeline;

use App\DTO\{Pipeline\CreatePipelineData, Pipeline\PipelineStageData, Pipeline\UpdatePipelineData};
use App\Models\{Pipeline\OpportunityFormSchema, Pipeline\Pipeline, Pipeline\PipelineStage};
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Webpatser\Uuid\Uuid;

class PipelineEntityService
{
    protected ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function createPipeline(CreatePipelineData $data): Pipeline
    {
        return tap(new Pipeline(), function (Pipeline $pipeline) use ($data) {
            $pipeline->{$pipeline->getKeyName()} = (string)Uuid::generate(4);
            $pipeline->space()->associate($data->space_id);
            $pipeline->pipeline_name = $data->pipeline_name;

            $pipelineStages = array_map(function (PipelineStageData $stageData) use ($pipeline) {
                return tap(new PipelineStage(), function (PipelineStage $pipelineStage) use ($stageData, $pipeline) {
                    $pipelineStage->{$pipelineStage->getKeyName()} = (string)Uuid::generate(4);
                    $pipelineStage->pipeline()->associate($pipeline);
                    $pipelineStage->unsetRelation('pipeline');
                    $pipelineStage->stage_name = $stageData->stage_name;
                    $pipelineStage->stage_order = $stageData->stage_order;
                });
            }, $data->pipeline_stages);

            $pipeline->setRelation('pipelineStages', new Collection($pipelineStages));

            $this->connection->transaction(function () use ($pipeline) {

                $pipeline->save();

                foreach ($pipeline->pipelineStages as $stage) {
                    $stage->save();
                }

            });

            $pipeline->unsetRelation('pipelineStages');

        });
    }

    public function updatePipeline(Pipeline $pipeline, UpdatePipelineData $data): Pipeline
    {
        return tap($pipeline, function (Pipeline $pipeline) use ($data) {
            $pipeline->space()->associate($data->space_id);
            $pipeline->pipeline_name = $data->pipeline_name;

            $pipelineStageDataKeys = array_filter(array_map(function (PipelineStageData $pipelineStageData) {
                return $pipelineStageData->stage_id;
            }, $data->pipeline_stages), fn(?string $stageId) => !is_null($stageId));

            $deletedStageModelKeys = $pipeline->pipelineStages()
                ->whereKeyNot($pipelineStageDataKeys)
                ->pluck('id');

            $pipelineStages = array_map(function (PipelineStageData $stageData) use ($pipeline) {
                return tap(new PipelineStage(), function (PipelineStage $pipelineStage) use ($stageData, $pipeline) {

                    if (is_null($stageData->stage_id)) {
                        $pipelineStage->{$pipelineStage->getKeyName()} = (string)Uuid::generate(4);
                    } else {
                        $pipelineStage->{$pipelineStage->getKeyName()} = $stageData->stage_id;
                        $pipelineStage->exists = true;
                    }

                    $pipelineStage->pipeline()->associate($pipeline);
                    $pipelineStage->unsetRelation('pipeline');
                    $pipelineStage->stage_name = $stageData->stage_name;
                    $pipelineStage->stage_order = $stageData->stage_order;
                });
            }, $data->pipeline_stages);

            $pipeline->setRelation('pipelineStages', new Collection($pipelineStages));

            $this->connection->transaction(function () use ($pipeline, $deletedStageModelKeys) {

                $pipeline->save();

                if (false === empty($deletedStageModelKeys)) {
                    $pipeline->pipelineStages()
                        ->whereKey($deletedStageModelKeys)
                        ->forceDelete();
                }

                foreach ($pipeline->pipelineStages as $stage) {
                    $stage->save();
                }

            });

            $pipeline->unsetRelation('pipelineStages');

        });
    }

    public function markPipelineAsDefault(Pipeline $pipeline): void
    {
        $pipeline->is_default = true;

        $this->connection->transaction(function () use ($pipeline) {
            Pipeline::query()
                ->whereKeyNot($pipeline->getKey())
                ->update(['is_default' => false]);

            $pipeline->save();
        });
    }

    public function deletePipeline(Pipeline $pipeline): void
    {
        $this->connection->transaction(function () use ($pipeline) {
            $pipeline->delete();
        });
    }

    public function updateOpportunityFormSchemaOfPipeline(Pipeline $pipeline, array $formSchema): void
    {
        /** @var OpportunityFormSchema $opportunityFormSchema */
        $opportunityFormSchema = with($pipeline->opportunityFormSchema, function (?OpportunityFormSchema $opportunityFormSchema) {

            // Instantiate a new OpportunityFormSchema entity,
            // if it is not present on Opportunity entity yet.
            if (is_null($opportunityFormSchema)) {
                return tap(new OpportunityFormSchema(), function (OpportunityFormSchema $opportunityFormSchema) {
                    $opportunityFormSchema->{$opportunityFormSchema->getKeyName()} = (string)Uuid::generate(4);
                });
            }

            return $opportunityFormSchema;

        });

        $opportunityFormSchema->form_data = $formSchema;

        $pipeline->opportunityFormSchema()->associate($opportunityFormSchema);

        $this->connection->transaction(function () use ($pipeline) {

            $pipeline->opportunityFormSchema->save();
            $pipeline->save();

        });
    }
}
