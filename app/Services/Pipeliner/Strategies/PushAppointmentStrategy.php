<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\GraphQl\PipelinerAppointmentIntegration;
use App\Models\Appointment\Appointment;
use App\Models\PipelinerModelUpdateLog;
use App\Services\Appointment\AppointmentDataMapper;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\Strategies\Concerns\SalesUnitsAware;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PushAppointmentStrategy implements PushStrategy
{
    use SalesUnitsAware;

    public function __construct(protected ConnectionInterface             $connection,
                                protected PipelinerAppointmentIntegration $appointmentIntegration,
                                protected PushSalesUnitStrategy           $pushSalesUnitStrategy,
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
            ->whereIn($model->salesUnit()->getQualifiedForeignKeyName(), Collection::make($this->getSalesUnits())->modelKeys())
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
        if (!$model instanceof Appointment) {
            throw new \TypeError(sprintf("Model must be an instance of %s.", Appointment::class));
        }

        if (null !== $model->owner) {
            $this->pushClientStrategy->sync($model->owner);
        }

        if (null !== $model->salesUnit) {
            $this->pushSalesUnitStrategy->sync($model->salesUnit);
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

    public function countPending(): int
    {
            $this->salesUnit ?? throw PipelinerSyncException::unsetSalesUnit();

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