<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\GraphQl\PipelinerAppointmentIntegration;
use App\Integrations\Pipeliner\Models\AppointmentEntity;
use App\Integrations\Pipeliner\Models\AppointmentFilterInput;
use App\Integrations\Pipeliner\Models\CloudObjectEntity;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\SalesUnitFilterInput;
use App\Models\Appointment\Appointment;
use App\Models\Attachment;
use App\Models\PipelinerModelScrollCursor;
use App\Services\Appointment\AppointmentDataMapper;
use App\Services\Attachment\AttachmentDataMapper;
use App\Services\Attachment\AttachmentFileService;
use App\Services\Pipeliner\Strategies\Concerns\SalesUnitsAware;
use App\Services\Pipeliner\Strategies\Contracts\PullStrategy;
use DateTimeInterface;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use JetBrains\PhpStorm\ArrayShape;

class PullAppointmentStrategy implements PullStrategy
{
    use SalesUnitsAware;

    public function __construct(protected ConnectionInterface             $connection,
                                protected LockProvider                    $lockProvider,
                                protected PipelinerAppointmentIntegration $appointmentIntegration,
                                protected AppointmentDataMapper           $dataMapper,
                                protected AttachmentFileService           $attachmentFileService,
                                protected AttachmentDataMapper            $attachmentDataMapper)
    {
    }

    /**
     * @param AppointmentEntity $entity
     * @return Model
     * @throws \Throwable
     */
    public function sync(object $entity): Model
    {
        if (!$entity instanceof AppointmentEntity) {
            throw new \TypeError(sprintf("Entity must be an instance of %s.", AppointmentEntity::class));
        }

        /** @var Appointment|null $appointment */
        $appointment = Appointment::query()
            ->withTrashed()
            ->where('pl_reference', $entity->id)
            ->first();

        if (null !== $appointment) {
            $updatedAppointment = $this->dataMapper->mapFromAppointmentEntity($entity);

            $this->dataMapper->mergeAttributesFrom($appointment, $updatedAppointment);

            $this->connection->transaction(static function () use ($appointment): void {
                $appointment->withoutTimestamps(static function (Appointment $appointment): void {
                    $appointment->save(['touch' => false]);
                });

                $appointment->reminder?->save();

                $appointment->opportunitiesHaveAppointment()->syncWithoutDetaching($appointment->opportunitiesHaveAppointment);
                $appointment->companiesHaveAppointment()->syncWithoutDetaching($appointment->companiesHaveAppointment);
                $appointment->companies()->sync($appointment->companies);
                $appointment->contacts()->sync($appointment->contacts);
                $appointment->opportunities()->sync($appointment->opportunities);
                $appointment->inviteesUsers()->sync($appointment->inviteesUsers);
                $appointment->inviteesContacts->each->save();
            });

            $this->syncRelationsOfAppointmentEntity($entity, $appointment);

            return $appointment;
        }

        $appointment = $this->dataMapper->mapFromAppointmentEntity($entity);

        $this->connection->transaction(static function () use ($appointment): void {
            $appointment->owner->save();

            $appointment->save();

            $appointment->reminder?->save();

            $appointment->opportunitiesHaveAppointment()->syncWithoutDetaching($appointment->opportunitiesHaveAppointment);
            $appointment->companiesHaveAppointment()->syncWithoutDetaching($appointment->companiesHaveAppointment);
            $appointment->companies()->attach($appointment->companies);
            $appointment->contacts()->attach($appointment->contacts);
            $appointment->opportunities()->attach($appointment->opportunities);
            $appointment->inviteesUsers()->attach($appointment->inviteesUsers);
            $appointment->inviteesContacts->each->save();
        });

        $this->syncRelationsOfAppointmentEntity($entity, $appointment);

        return $appointment;
    }

    private function syncRelationsOfAppointmentEntity(AppointmentEntity $entity, Appointment $model): void
    {
        foreach ($entity->documents as $document) {
            $this->syncDocument($document, $model);
        }
    }

    private function syncDocument(CloudObjectEntity $entity, Appointment $model): void
    {
        /** @var Attachment|null $attachment */
        $attachment = Attachment::query()
            ->where('pl_reference', $entity->id)
            ->withTrashed()
            ->first();

        // skip if already exists
        if (null !== $attachment) {
            return;
        }

        $metadata = $this->attachmentFileService->downloadFromUrl($entity->url);

        tap($this->attachmentDataMapper->mapFromCloudObjectEntity($entity, $metadata), function (Attachment $attachment) use
        (
            $model
        ): void {
            $this->connection->transaction(static function () use ($model, $attachment): void {
                $attachment->save();
                $model->attachments()->syncWithoutDetaching($attachment);
            });
        });
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
        return $this->appointmentIntegration->scroll(
            ...$this->resolveScrollParameters()
        );
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
            $totalCount++;

            $iterator->next();
        }

        return [$totalCount, $lastId];
    }

    private function getMostRecentScrollCursor(): ?PipelinerModelScrollCursor
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return PipelinerModelScrollCursor::query()
            ->where('model_type', $this->getModelType())
            ->latest()
            ->first();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Appointment;
    }

    #[ArrayShape(['id' => 'string', 'revision' => 'int', 'created' => DateTimeInterface::class,
        'modified' => DateTimeInterface::class])]
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
}