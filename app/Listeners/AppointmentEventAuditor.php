<?php

namespace App\Listeners;

use App\Enum\Priority;
use App\Events\Appointment\AppointmentCreated;
use App\Events\Appointment\AppointmentDeleted;
use App\Events\Appointment\AppointmentUpdated;
use App\Models\Appointment\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\ChangesDetector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Arr;

class AppointmentEventAuditor implements ShouldQueue
{
    public function __construct(
        protected readonly ActivityLogger $activityLogger,
        protected readonly ChangesDetector $changesDetector,
    ) {
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            AppointmentCreated::class => [
                static::handleCreatedEvent(...),
            ],
            AppointmentUpdated::class => [
                static::handleUpdatedEvent(...),
            ],
            AppointmentDeleted::class => [
                static::handleDeletedEvent(...),
            ],
        ];
    }

    public function handleCreatedEvent(AppointmentCreated $event): void
    {
        $this->activityLogger
            ->on($event->appointment)
            ->by($event->causer)
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [],
                ChangesDetector::NEW_ATTRS_KEY => $this->attributesToBeLogged($event->appointment),
            ])
            ->log('created');

        notification()
            ->for($event->appointment->owner)
            ->message(sprintf(
                'New %s [%s] created',
                $event->appointment->activity_type->value,
                $event->appointment->subject,
            ))
            ->priority(Priority::Low)
            ->push();
    }

    public function handleUpdatedEvent(AppointmentUpdated $event): void
    {
        $this->activityLogger
            ->on($event->appointment)
            ->by($event->causer)
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    oldAttributeValues: $this->attributesToBeLogged($event->oldAppointment),
                    newAttributeValues: $this->attributesToBeLogged($event->appointment)
                )
            )
            ->log('updated');
    }

    public function handleDeletedEvent(AppointmentDeleted $event): void
    {
        $this->activityLogger
            ->on($event->appointment)
            ->by($event->causer)
            ->log('deleted');

        notification()
            ->for($event->appointment->owner)
            ->message(sprintf(
                '%s [%s] deleted',
                $event->appointment->activity_type->value,
                $event->appointment->subject,
            ))
            ->priority(Priority::Low)
            ->push();
    }

    private function attributesToBeLogged(Appointment $appointment): array
    {
        $attributes = Arr::only($appointment->attributesToArray(), [
            'activity_type',
            'subject',
            'description',
            'start_date',
            'end_date',
            'location',
        ]);

        $attributes['sales_unit'] = $appointment->salesUnit?->unit_name;
        $attributes['linked_opportunities'] = $appointment->opportunities
            ->lazy()
            ->map(static fn(Opportunity $model): string => "[{$model->getIdForHumans()}]")
            ->join('; ');
        $attributes['linked_companies'] = $appointment->companies
            ->lazy()
            ->map(static fn(Company $model): string => "[{$model->getIdForHumans()}]")
            ->join('; ');
        $attributes['linked_contacts'] = $appointment->contacts
            ->lazy()
            ->map(static fn(Contact $model): string => "[{$model->getIdForHumans()}]")
            ->join('; ');
        $attributes['users'] = $appointment->users
            ->lazy()
            ->map(static fn(User $model): string => "[{$model->getIdForHumans()}]")
            ->join('; ');
        $attributes['reminder'] = $appointment->reminder?->getIdForHumans();


        return $attributes;
    }
}
