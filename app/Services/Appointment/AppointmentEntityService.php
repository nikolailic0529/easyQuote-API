<?php

namespace App\Services\Appointment;

use App\Contracts\CauserAware;
use App\Contracts\HasOwnAppointments;
use App\DTO\Appointment\CreateAppointmentData;
use App\DTO\Appointment\SetAppointmentReminderData;
use App\DTO\Appointment\UpdateAppointmentData;
use App\Events\Appointment\AppointmentCreated;
use App\Events\Appointment\AppointmentDeleted;
use App\Events\Appointment\AppointmentUpdated;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentReminder;
use App\Models\Appointment\ModelHasAppointments;
use App\Models\Task\TaskReminder;
use App\Models\User;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Webpatser\Uuid\Uuid;

class AppointmentEntityService implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected ConnectionInterface $connection,
        protected EventDispatcher $eventDispatcher,
        protected readonly AppointmentDataMapper $dataMapper,
    ) {
    }

    public function createAppointment(
        CreateAppointmentData $data,
        HasOwnAppointments&Model $modelHasAppointment
    ): Appointment {
        return tap(new Appointment(), function (Appointment $appointment) use ($data, $modelHasAppointment): void {
            $appointment->{$appointment->getKeyName()} = (string) Uuid::generate(4);
            $appointment->salesUnit()->associate($data->sales_unit_id);
            $appointment->owner()->associate($this->causer);
            $appointment->activity_type = $data->activity_type;
            $appointment->subject = $data->subject;
            $appointment->description = $data->description;
            $appointment->location = (string) $data->location;

            $appointment->start_date = Carbon::instance($data->start_date);
            $appointment->end_date = Carbon::instance($data->end_date);

            $reminder = isset($data->reminder)
                ? tap(new AppointmentReminder(),
                    function (AppointmentReminder $reminder) use ($appointment, $data): void {
                        $reminder->appointment()->associate($appointment);
                        if ($this->causer instanceof User) {
                            $reminder->owner()->associate($this->causer);
                        }
                        $reminder->start_date_offset = $data->reminder->start_date_offset;
                        $reminder->status = $data->reminder->status;
                    })
                : null;

            $this->connection->transaction(function () use (
                $modelHasAppointment,
                $appointment,
                $reminder,
                $data
            ): void {
                $appointment->save();

                $reminder?->save();

                if (is_array($data->invitee_user_relations)) {
                    $appointment->inviteesUsers()->attach($data->invitee_user_relations);
                }

                if (is_array($data->invitee_contact_relations)) {
                    $appointment->inviteesContacts()->attach($data->invitee_contact_relations);
                }

                if (is_array($data->company_relations)) {
                    $appointment->companies()->attach($data->company_relations);
                }

                if (is_array($data->opportunity_relations)) {
                    $appointment->opportunities()->attach($data->opportunity_relations);
                }

                if (is_array($data->contact_relations)) {
                    $appointment->contacts()->attach($data->contact_relations);
                }

                if (is_array($data->user_relations)) {
                    $appointment->users()->attach($data->user_relations);
                }

                if (is_array($data->rescue_quote_relations)) {
                    $appointment->rescueQuotes()->attach($data->rescue_quote_relations);
                }

                if (is_array($data->worldwide_quote_relations)) {
                    $appointment->worldwideQuotes()->attach($data->worldwide_quote_relations);
                }

                if (is_array($data->attachment_relations)) {
                    $appointment->attachments()->attach($data->attachment_relations);
                }

                $modelHasAppointment->ownAppointments()->attach($appointment);

                $this->touchRelated($appointment);
            });

            $this->eventDispatcher->dispatch(
                new AppointmentCreated($appointment, $this->causer)
            );
        });
    }

    public function updateAppointment(Appointment $appointment, UpdateAppointmentData $data): Appointment
    {
        return tap($appointment, function (Appointment $appointment) use ($data): void {
            $existingReminder = $this->causer instanceof User
                ? $appointment->activeReminders()->whereBelongsTo($this->causer, 'owner')->first()
                : null;

            $appointment->setRelation('reminder', $existingReminder);
            $oldAppointment = $this->dataMapper->cloneAppointment($appointment);

            $appointment->salesUnit()->associate($data->sales_unit_id);
            $appointment->activity_type = $data->activity_type;
            $appointment->subject = $data->subject;
            $appointment->description = $data->description;
            $appointment->location = (string) $data->location;

            $appointment->start_date = Carbon::instance($data->start_date);
            $appointment->end_date = Carbon::instance($data->end_date);

            $reminder = isset($data->reminder)
                ? tap($existingReminder ?? new AppointmentReminder(),
                    function (AppointmentReminder $reminder) use ($appointment, $data): void {
                        $reminder->appointment()->associate($appointment);
                        $appointment->setRelation('reminder', $reminder);
                        if (false === $reminder->exists) {
                            $reminder->owner()->associate($this->causer);
                        }
                        $reminder->start_date_offset = $data->reminder->start_date_offset;
                        $reminder->status = $data->reminder->status;
                    })
                : null;

            $this->connection->transaction(function () use ($appointment, $reminder, $data): void {
                $appointment->save();

                $reminder?->save();

                if (is_array($data->invitee_user_relations)) {
                    $appointment->inviteesUsers()->sync($data->invitee_user_relations);
                }

                if (is_array($data->invitee_contact_relations)) {
                    $appointment->inviteesContacts()->sync($data->invitee_contact_relations);
                }

                if (is_array($data->company_relations)) {
                    $appointment->companies()->sync($data->company_relations);
                }

                if (is_array($data->opportunity_relations)) {
                    $appointment->opportunities()->sync($data->opportunity_relations);
                }

                if (is_array($data->contact_relations)) {
                    $appointment->contacts()->sync($data->contact_relations);
                }

                if (is_array($data->user_relations)) {
                    $appointment->users()->sync($data->user_relations);
                }

                if (is_array($data->rescue_quote_relations)) {
                    $appointment->rescueQuotes()->sync($data->rescue_quote_relations);
                }

                if (is_array($data->worldwide_quote_relations)) {
                    $appointment->worldwideQuotes()->sync($data->worldwide_quote_relations);
                }

                if (is_array($data->attachment_relations)) {
                    $appointment->attachments()->sync($data->attachment_relations);
                }

                $this->touchRelated($appointment);
            });

            $appointment->refresh();

            $this->eventDispatcher->dispatch(new AppointmentUpdated(
                appointment: $appointment,
                oldAppointment: $oldAppointment,
                causer: $this->causer,
            ));
        });
    }

    public function updateReminder(
        AppointmentReminder $reminder,
        SetAppointmentReminderData $data
    ) {
        return tap($reminder, function (AppointmentReminder $reminder) use ($data): void {
            $reminder->forceFill($data->all());

            $this->connection->transaction(static function () use ($reminder): void {
                $reminder->save();
            });
        });
    }

    public function deleteReminder(AppointmentReminder $reminder): void
    {
        $this->connection->transaction(static function () use ($reminder): void {
            $reminder->delete();
        });
    }

    public function deleteAppointment(Appointment $appointment): void
    {
        $this->connection->transaction(function () use ($appointment): void {
            $appointment->delete();
            $this->touchRelated($appointment);
        });

        $this->eventDispatcher->dispatch(new AppointmentDeleted(appointment: $appointment, causer: $this->causer));
    }

    protected function touchRelated(Appointment $appointment): void
    {
        foreach ($appointment->modelsHaveAppointment as $model) {
            /** @var $model ModelHasAppointments */
            $model->related?->touch();
        }
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn() => $this->causer = $causer);
    }
}