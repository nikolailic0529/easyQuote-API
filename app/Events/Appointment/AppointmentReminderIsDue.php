<?php

namespace App\Events\Appointment;

use App\Models\Appointment\AppointmentReminder;
use Carbon\Carbon;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

final class AppointmentReminderIsDue implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly AppointmentReminder $reminder
    ) {
    }

    public function broadcastOn(): array
    {
        $user = $this->reminder->owner;

        if (null === $user) {
            return [];
        }

        return [new PrivateChannel("user.{$user->getKey()}")];
    }

    public function broadcastAs(): string
    {
        return 'appointment.reminder';
    }

    public function broadcastWith(): array
    {
        $user = $this->reminder->owner;

        $tz = $user->timezone->utc ?? config('app.timezone');

        $appointment = $this->reminder->appointment;

        return [
            'id' => $appointment->getKey(),
            'reminder_id' => $this->reminder->getKey(),
            'activity_type' => $appointment->activity_type,
            'subject' => $appointment->subject,
            'description' => $appointment->description,
            'location' => $appointment->location,
            'start_date' => \Illuminate\Support\Carbon::instance($appointment->start_date)
                ->tz($tz)
                ->format(config('date.format_time')),
            'end_date' => Carbon::instance($appointment->end_date)->tz($tz)->format(config('date.format_time')),
        ];
    }
}
