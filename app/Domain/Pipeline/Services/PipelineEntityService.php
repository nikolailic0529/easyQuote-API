<?php

namespace App\Domain\Pipeline\Services;

use App\Domain\Pipeline\DataTransferObjects\CreatePipelineData;
use App\Domain\Pipeline\DataTransferObjects\PipelineStageData;
use App\Domain\Pipeline\DataTransferObjects\PutPipelineDataCollection;
use App\Domain\Pipeline\DataTransferObjects\UpdatePipelineData;
use App\Domain\Pipeline\Events\PipelineCreated;
use App\Domain\Pipeline\Events\PipelineDeleted;
use App\Domain\Pipeline\Events\PipelineUpdated;
use App\Domain\Pipeline\Models\Pipeline;
use App\Domain\Pipeline\Models\PipelineStage;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webpatser\Uuid\Uuid;

class PipelineEntityService
{
    protected ConnectionInterface $connection;
    protected EventDispatcher $eventDispatcher;
    protected ValidatorInterface $validator;

    public function __construct(ConnectionInterface $connection, EventDispatcher $eventDispatcher, ValidatorInterface $validator)
    {
        $this->connection = $connection;
        $this->eventDispatcher = $eventDispatcher;
        $this->validator = $validator;
    }

    public function bulkCreateOrUpdatePipelines(PutPipelineDataCollection $collection): Collection
    {
        foreach ($collection as $data) {
            $violations = $this->validator->validate($data);

            if (count($violations)) {
                throw new ValidationFailedException($data, $violations);
            }
        }

        $collection->rewind();

        $modelKeys = [];

        foreach ($collection as $data) {
            if (false === is_null($data->pipeline_id)) {
                $modelKeys[] = $data->pipeline_id;
            }
        }

        $collection->rewind();

        /** @var \App\Domain\Pipeline\Models\Pipeline[] $pipelineDictionary */
        $pipelineDictionary = \App\Domain\Pipeline\Models\Pipeline::query()->whereKey($modelKeys)->get()->getDictionary();
        $missingPipelineModels = Pipeline::query()->whereKeyNot($modelKeys)->get();

        $pipelineModels = [];

        $pipelineOrder = 0;

        foreach ($collection as $data) {
            if (is_null($data->pipeline_id)) {
                $pipelineModels[] = $this->createPipeline(new CreatePipelineData([
                    'space_id' => $data->space_id,
                    'pipeline_name' => $data->pipeline_name,
                    'pipeline_stages' => $data->pipeline_stages,
                    'is_default' => $data->is_default,
                    'pipeline_order' => $pipelineOrder++,
                ]));
            } else {
                $pipelineModels[] = $this->updatePipeline($pipelineDictionary[$data->pipeline_id], new UpdatePipelineData([
                    'space_id' => $data->space_id,
                    'pipeline_name' => $data->pipeline_name,
                    'pipeline_stages' => $data->pipeline_stages,
                    'is_default' => $data->is_default,
                    'pipeline_order' => $pipelineOrder++,
                ]));
            }
        }

        foreach ($missingPipelineModels as $model) {
            $this->deletePipeline($model);
        }

        return new Collection($pipelineModels);
    }

    public function createPipeline(CreatePipelineData $data): Pipeline
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationFailedException($data, $violations);
        }

        return tap(new \App\Domain\Pipeline\Models\Pipeline(), function (Pipeline $pipeline) use ($data) {
            $pipeline->{$pipeline->getKeyName()} = (string) Uuid::generate(4);
            $pipeline->space()->associate($data->space_id);
            $pipeline->pipeline_name = $data->pipeline_name;
            $pipeline->is_default = $data->is_default;
            $pipeline->pipeline_order = $data->pipeline_order;
            $pipeline->is_system = 0;

            $pipelineStages = array_map(function (PipelineStageData $stageData) use ($pipeline) {
                return tap(new PipelineStage(), function (PipelineStage $pipelineStage) use ($stageData, $pipeline) {
                    $pipelineStage->{$pipelineStage->getKeyName()} = (string) Uuid::generate(4);
                    $pipelineStage->pipeline()->associate($pipeline);
                    $pipelineStage->unsetRelation('pipeline');
                    $pipelineStage->stage_name = $stageData->stage_name;
                    $pipelineStage->stage_order = $stageData->stage_order;
                    $pipelineStage->stage_percentage = $stageData->stage_percentage;
                });
            }, $data->pipeline_stages);

            $pipeline->setRelation('pipelineStages', new Collection($pipelineStages));

            $this->connection->transaction(function () use ($pipeline) {
                $pipeline->save();

                foreach ($pipeline->pipelineStages as $stage) {
                    $stage->save();
                }
            });

            foreach ($pipeline->pipelineStages as $stage) {
                $stage->unsetRelation('pipeline');
            }

            $this->eventDispatcher->dispatch(
                new PipelineCreated($pipeline)
            );
        });
    }

    public function updatePipeline(
        Pipeline $pipeline, UpdatePipelineData $data): Pipeline
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationFailedException($data, $violations);
        }

        return tap($pipeline, function (Pipeline $pipeline) use ($data) {
            $pipeline->space()->associate($data->space_id);
            $pipeline->pipeline_name = $data->pipeline_name;
            $pipeline->pipeline_order = $data->pipeline_order;
            $pipeline->is_default = $data->is_default;

            $pipelineStageDataKeys = array_filter(array_map(function (PipelineStageData $pipelineStageData) {
                return $pipelineStageData->stage_id;
            }, $data->pipeline_stages), fn (?string $stageId) => !is_null($stageId));

            $deletedStageModelKeys = $pipeline->pipelineStages()
                ->whereKeyNot($pipelineStageDataKeys)
                ->pluck('id');

            $pipelineStages = array_map(function (PipelineStageData $stageData) use ($pipeline) {
                return tap(new PipelineStage(), function (PipelineStage $pipelineStage) use ($stageData, $pipeline) {
                    if (is_null($stageData->stage_id)) {
                        $pipelineStage->{$pipelineStage->getKeyName()} = (string) Uuid::generate(4);
                    } else {
                        $pipelineStage->{$pipelineStage->getKeyName()} = $stageData->stage_id;
                        $pipelineStage->exists = true;
                    }

                    $pipelineStage->pipeline()->associate($pipeline);
                    $pipelineStage->unsetRelation('pipeline');
                    $pipelineStage->stage_name = $stageData->stage_name;
                    $pipelineStage->stage_order = $stageData->stage_order;
                    $pipelineStage->stage_percentage = $stageData->stage_percentage;
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

            foreach ($pipeline->pipelineStages as $stage) {
                $stage->unsetRelation('pipeline');
            }

            $this->eventDispatcher->dispatch(
                new PipelineUpdated($pipeline)
            );
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

        $this->eventDispatcher->dispatch(
            new PipelineDeleted($pipeline)
        );
    }
}
