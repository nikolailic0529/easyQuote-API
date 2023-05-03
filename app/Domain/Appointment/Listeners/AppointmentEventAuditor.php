<?php

namespace App\Domain\Appointment\Listeners;

use App\Domain\Activity\Services\ActivityLogger;
use App\Domain\Activity\Services\ChangesDetector;
use App\Domain\Appointment\Events\AppointmentCreated;
use App\Domain\Appointment\Events\AppointmentDeleted;
use App\Domain\Appointment\Events\AppointmentUpdated;
use App\Domain\Appointment\Models\Appointment;
use App\Domain\Appointment\Notifications\AppointmentCreatedNotification;
use App\Domain\Appointment\Notifications\AppointmentDeletedNotification;
use App\Domain\Appointment\Notifications\InvitedToAppointmentNotification;
use App\Domain\Appointment\Notifications\RevokedInvitationFromAppointmentNotification;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;

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
                static::auditCreatedEvent(...),
                static::notifyAboutCreated(...),
            ],
            AppointmentUpdated::class => [
                static::auditUpdatedEvent(...),
                static::notifyAboutNewlyInvitedUsers(...),
            ],
            AppointmentDeleted::class => [
                static::auditDeletedEvent(...),
                static::notifyAboutDeleted(...),
            ],
        ];
    }

    public function auditCreatedEvent(AppointmentCreated $event): void
    {
        $this->activityLogger
            ->on($event->appointment)
            ->by($event->causer)
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [],
                ChangesDetector::NEW_ATTRS_KEY => $this->attributesToBeLogged($event->appointment),
            ])
            ->log('created');
    }

    public function notifyAboutCreated(AppointmentCreated $event): void
    {
        if ($event->appointment->owner) {
            $event->appointment->owner->notify(new AppointmentCreatedNotification($event->appointment));
        }
    }

    public function auditUpdatedEvent(AppointmentUpdated $event): void
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
            ->submitEmptyLogs(false)
            ->log('updated');
    }

    public function notifyAboutNewlyInvitedUsers(AppointmentUpdated $event): void
    {
        $newlyInvitedUsers = $event->appointment->users->diff($event->oldAppointment->users);
        $newlyRevokedUsers = $event->oldAppointment->users->diff($event->appointment->users);

        Notification::send($newlyInvitedUsers, new InvitedToAppointmentNotification($event->appointment));
        Notification::send($newlyRevokedUsers, new RevokedInvitationFromAppointmentNotification($event->appointment));
    }

    public function auditDeletedEvent(AppointmentDeleted $event): void
    {
        $this->activityLogger
            ->on($event->appointment)
            ->by($event->causer)
            ->log('deleted');
    }

    public function notifyAboutDeleted(AppointmentDeleted $event): void
    {
        if ($event->appointment->owner) {
            $event->appointment->owner->notify(new AppointmentDeletedNotification($event->appointment));
        }
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
            ->map(static fn (Opportunity $model): string => "[{$model->getIdForHumans()}]")
            ->join('; ');
        $attributes['linked_companies'] = $appointment->companies
            ->lazy()
            ->map(static fn (Company $model): string => "[{$model->getIdForHumans()}]")
            ->join('; ');
        $attributes['linked_contacts'] = $appointment->contacts
            ->lazy()
            ->map(static fn (Contact $model): string => "[{$model->getIdForHumans()}]")
            ->join('; ');
        $attributes['users'] = $appointment->users
            ->lazy()
            ->map(static fn (User $model): string => "[{$model->getIdForHumans()}]")
            ->join('; ');
        $attributes['reminder'] = $appointment->reminder?->getIdForHumans();

        return $attributes;
    }
}
