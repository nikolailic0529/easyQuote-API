<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\GraphQl\PipelinerAppointmentIntegration;
use App\Models\Appointment\Appointment;
use App\Models\Pipeline\Pipeline;
use App\Models\PipelinerModelUpdateLog;
use App\Services\Appointment\AppointmentDataMapper;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use JetBrains\PhpStorm\ArrayShape;

class PushAppointmentStrategy implements PushStrategy
{
    protected ?Pipeline $pipeline = null;

    public function __construct(protected ConnectionInterface             $connection,
                                protected PipelinerAppointmentIntegration $appointmentIntegration,
                                protected PushClientStrategy              $pushClientStrategy,
                                protected AppointmentDataMapper           $dataMapper,
                                protected LockProvider                    $lockProvider)
    {
    }

    private function modelsToBeUpdatedQuery(): Builder
    {
        $lastUpdatedAt = PipelinerModelUpdateLog::query()
            ->where('model_type', $this->getModelType())
            ->latest()
            ->value('latest_model_updated_at');

        $model = new Appointment();

        return $model->newQuery()
            ->orderBy($model->getQualifiedUpdatedAtColumn())
            ->where(static function (Builder $builder) use ($model): void {
                $builder->whereColumn($model->getQualifiedUpdatedAtColumn(), '>', $model->getQualifiedCreatedAtColumn())
                    ->orWhereNull($model->qualifyColumn('pl_reference'));
            })
            ->unless(is_null($lastUpdatedAt), static function (Builder $builder) use ($model, $lastUpdatedAt): void {
                $builder->where($model->getQualifiedUpdatedAtColumn(), '>', $lastUpdatedAt);
            });
    }

    /**
     * @param Appointment $model
     * @return void
     * @throws \App\Integrations\Pipeliner\Exceptions\GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Throwable
     */
    public function sync(Model $model): void
    {
        if (null !== $model->owner) {
            $this->pushClientStrategy->sync($model->owner);
        }

        if (null === $model->pl_reference) {
            $input = $this->dataMapper->mapPipelinerCreateAppointmentInput($model);

            $entity = $this->appointmentIntegration->create($input);

            tap($model, function (Appointment $appointment) use ($entity): void {
                $appointment->pl_reference = $entity->id;

                $this->connection->transaction(static fn() => $appointment->push());
            });
        } else {
            $entity = $this->appointmentIntegration->getById($model->pl_reference);

            $input = $this->dataMapper->mapPipelinerUpdateAppointmentInput($model, $entity);

            $this->appointmentIntegration->update($input);
        }
    }

    public function setPipeline(Pipeline $pipeline): static
    {
        return tap($this, fn() => $this->pipeline = $pipeline);
    }

    public function getPipeline(): ?Pipeline
    {
        return $this->pipeline;
    }

    public function countPending(): int
    {
        $this->pipeline ?? throw PipelinerSyncException::unsetPipeline();

        return $this->modelsToBeUpdatedQuery()->count();
    }

    public function iteratePending(): \Traversable
    {
        return $this->modelsToBeUpdatedQuery()->lazyById(100);
    }

    public function getModelType(): string
    {
        return (new Appointment())->getMorphClass();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Appointment;
    }
}