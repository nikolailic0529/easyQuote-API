<?php

namespace App\Domain\Pipeliner\Services\Strategies;

use App\Domain\Appointment\Events\AppointmentCreated;
use App\Domain\Appointment\Events\AppointmentUpdated;
use App\Domain\Appointment\Models\Appointment;
use App\Domain\Appointment\Services\AppointmentDataMapper;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerAppointmentIntegration;
use App\Domain\Pipeliner\Integration\Models\AppointmentEntity;
use App\Domain\Pipeliner\Integration\Models\AppointmentFilterInput;
use App\Domain\Pipeliner\Integration\Models\EntityFilterStringField;
use App\Domain\Pipeliner\Integration\Models\SalesUnitFilterInput;
use App\Domain\Pipeliner\Models\PipelinerModelScrollCursor;
use App\Domain\Pipeliner\Services\Strategies\Concerns\SalesUnitsAware;
use App\Domain\Pipeliner\Services\Strategies\Contracts\PullStrategy;
use DateTimeInterface;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use JetBrains\PhpStorm\ArrayShape;

class PullAppointmentStrategy implements PullStrategy
{
    use SalesUnitsAware;

    public function __construct(
        protected ConnectionInterface $connection,
        protected LockProvider $lockProvider,
        protected PipelinerAppointmentIntegration $appointmentIntegration,
        protected PullAttachmentStrategy $pullAttachmentStrategy,
        protected AppointmentDataMapper $dataMapper,
        protected EventDispatcher $eventDispatcher,
    ) {
    }

    /**
     * @param AppointmentEntity $entity
     *
     * @throws \Throwable
     */
    public function sync(object $entity): Model
    {
        if (!$entity instanceof AppointmentEntity) {
            throw new \TypeError(sprintf('Entity must be an instance of %s.', AppointmentEntity::class));
        }

        /** @var Appointment|null $appointment */
        $appointment = Appointment::query()
            ->withTrashed()
            ->where('pl_reference', $entity->id)
            ->first();

        if (null !== $appointment) {
            $oldAppointment = $this->dataMapper->cloneAppointment($appointment);

            $updatedAppointment = $this->dataMapper->mapFromAppointmentEntity($entity);

            $this->dataMapper->mergeAttributesFrom($appointment, $updatedAppointment);

            $this->connection->transaction(static function () use ($appointment): void {
                $appointment->withoutTimestamps(static function (Appointment $appointment): void {
                    $appointment->save(['touch' => false]);
                });

                $appointment->reminder?->save();

                $appointment->opportunitiesHaveAppointment()
                    ->syncWithoutDetaching($appointment->opportunitiesHaveAppointment);
                $appointment->companiesHaveAppointment()->syncWithoutDetaching($appointment->companiesHaveAppointment);
                $appointment->companies()->sync($appointment->companies);
                $appointment->contacts()->sync($appointment->contacts);
                $appointment->opportunities()->sync($appointment->opportunities);
                $appointment->inviteesUsers()->sync($appointment->inviteesUsers);
                $appointment->inviteesContacts->each->save();
            });

            $this->syncRelationsOfAppointmentEntity($entity, $appointment);

            $this->eventDispatcher->dispatch(new AppointmentUpdated(
                appointment: $appointment,
                oldAppointment: $oldAppointment,
                causer: null,
            ));

            return $appointment;
        }

        $appointment = $this->dataMapper->mapFromAppointmentEntity($entity);

        $this->connection->transaction(static function () use ($appointment): void {
            $appointment->owner->save();

            $appointment->save();

            $appointment->reminder?->save();

            $appointment->opportunitiesHaveAppointment()
                ->syncWithoutDetaching($appointment->opportunitiesHaveAppointment);
            $appointment->companiesHaveAppointment()->syncWithoutDetaching($appointment->companiesHaveAppointment);
            $appointment->companies()->attach($appointment->companies);
            $appointment->contacts()->attach($appointment->contacts);
            $appointment->opportunities()->attach($appointment->opportunities);
            $appointment->inviteesUsers()->attach($appointment->inviteesUsers);
            $appointment->inviteesContacts->each->save();
        });

        $this->syncRelationsOfAppointmentEntity($entity, $appointment);

        $this->eventDispatcher->dispatch(new AppointmentCreated(
            appointment: $appointment,
            causer: null,
        ));

        return $appointment;
    }

    private function syncRelationsOfAppointmentEntity(AppointmentEntity $entity, Appointment $model): void
    {
        $attachments = collect($entity->documents)
            ->lazy()
            ->chunk(50)
            ->map(function (LazyCollection $collection): array {
                return $this->pullAttachmentStrategy->batch(...$collection->all());
            })
            ->collapse()
            ->pipe(static function (LazyCollection $collection) {
                return Collection::make($collection->all());
            });

        if ($attachments->isNotEmpty()) {
            $this->connection->transaction(
                static fn () => $model->attachments()->syncWithoutDetaching($attachments)
            );
        }
    }

    public function syncByReference(string $reference): Model
    {
        return $this->sync(
            $this->appointmentIntegration->getById($reference)
        );
    }

    public function countPending(): int
    {
        [$count, $lastId] = $this->computeTotalEntitiesCountAndLastIdToPull();

        return $count;
    }

    public function iteratePending(): \Traversable
    {
        return LazyCollection::make(function (): \Generator {
            yield from $this->appointmentIntegration->simpleScroll(
                ...$this->resolveScrollParameters()
            );
        })
            ->values();
    }

    public function getModelType(): string
    {
        return (new Appointment())->getMorphClass();
    }

    #[ArrayShape(['after' => 'string|null', 'filter' => AppointmentFilterInput::class])]
    private function resolveScrollParameters(): array
    {
        $filter = AppointmentFilterInput::new();
        $unitFilter = SalesUnitFilterInput::new()->name(EntityFilterStringField::eq(
            ...collect($this->getSalesUnits())->pluck('unit_name')
        ));
        $filter->unit($unitFilter);

        return [
            'after' => $this->getMostRecentScrollCursor()?->cursor,
            'filter' => $filter,
        ];
    }

    private function computeTotalEntitiesCountAndLastIdToPull(): array
    {
        /** @var \Generator $iterator */
        $iterator = $this->appointmentIntegration->simpleScroll(
            ...$this->resolveScrollParameters(),
            ...['first' => 1_000]
        );

        $totalCount = 0;
        $lastId = null;

        while ($iterator->valid()) {
            $lastId = $iterator->current();
            ++$totalCount;

            $iterator->next();
        }

        return [$totalCount, $lastId];
    }

    private function getMostRecentScrollCursor(): ?PipelinerModelScrollCursor
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return PipelinerModelScrollCursor::query()
            ->where('model_type', $this->getModelType())
            ->latest()
            ->first();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Appointment;
    }

    #[ArrayShape([
        'id' => 'string', 'revision' => 'int', 'created' => \DateTimeInterface::class,
        'modified' => \DateTimeInterface::class,
    ])]
    public function getMetadata(string $reference): array
    {
        $entity = $this->appointmentIntegration->getById($reference);

        return [
            'id' => $entity->id,
            'revision' => $entity->revision,
            'created' => $entity->created,
            'modified' => $entity->modified,
        ];
    }

    public function getByReference(string $reference): object
    {
        return $this->appointmentIntegration->getById($reference);
    }
}
