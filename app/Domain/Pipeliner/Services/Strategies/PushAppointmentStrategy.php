<?php

namespace App\Domain\Pipeliner\Services\Strategies;

use App\Domain\Appointment\Models\Appointment;
use App\Domain\Appointment\Services\AppointmentDataMapper;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerAppointmentIntegration;
use App\Domain\Pipeliner\Models\PipelinerModelUpdateLog;
use App\Domain\Pipeliner\Services\Exceptions\PipelinerSyncException;
use App\Domain\Pipeliner\Services\Strategies\Concerns\SalesUnitsAware;
use App\Domain\Pipeliner\Services\Strategies\Contracts\PushStrategy;
use Clue\React\Mq\Queue;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

use function React\Async\async;
use function React\Async\await;

class PushAppointmentStrategy implements PushStrategy
{
    use SalesUnitsAware;

    public function __construct(protected ConnectionInterface $connection,
                                protected PipelinerAppointmentIntegration $appointmentIntegration,
                                protected PushSalesUnitStrategy $pushSalesUnitStrategy,
                                protected PushClientStrategy $pushClientStrategy,
                                protected PushAttachmentStrategy $pushAttachmentStrategy,
                                protected AppointmentDataMapper $dataMapper,
                                protected LockProvider $lockProvider)
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
     * @param \App\Domain\Appointment\Models\Appointment $model
     *
     * @throws \App\Domain\Pipeliner\Integration\Exceptions\GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Throwable
     */
    public function sync(Model $model): void
    {
        if (!$model instanceof Appointment) {
            throw new \TypeError(sprintf('Model must be an instance of %s.', Appointment::class));
        }

        if (null !== $model->owner) {
            $this->pushClientStrategy->sync($model->owner);
        }

        if (null !== $model->salesUnit) {
            $this->pushSalesUnitStrategy->sync($model->salesUnit);
        }

        $this->pushAttachmentsOfAppointment($model);

        if (null === $model->pl_reference) {
            $input = $this->dataMapper->mapPipelinerCreateAppointmentInput($model);

            $entity = $this->appointmentIntegration->create($input);

            tap($model, function (Appointment $appointment) use ($entity): void {
                $appointment->pl_reference = $entity->id;

                $this->connection->transaction(static fn () => $appointment->push());
            });
        } else {
            $entity = $this->appointmentIntegration->getById($model->pl_reference);

            $input = $this->dataMapper->mapPipelinerUpdateAppointmentInput($model, $entity);

            $this->appointmentIntegration->update($input);
        }
    }

    public function pushAttachmentsOfAppointment(Appointment $appointment): void
    {
        $queue = Queue::all(10, $appointment->attachments->all(), async(function (Attachment $attachment): void {
            $this->pushAttachmentStrategy->sync($attachment);
        }));

        await($queue);
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

    public function getByReference(string $reference): object
    {
        return Appointment::query()->findOrFail($reference);
    }
}
