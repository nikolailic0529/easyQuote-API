<?php

namespace App\Domain\Appointment\Notifications;

use App\Domain\Appointment\Models\Appointment;
use App\Domain\Priority\Enum\Priority;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitedToAppointmentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Appointment $appointment
    ) {
    }

    public function via(mixed $notifiable): array
    {
        return ['mail', 'database.custom'];
    }

    public function shouldSend(mixed $notifiable, string $channel): bool
    {
        return true;
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Activity')
            ->line($this->getMessage())
            ->line('Thank you for using our application!');
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'message' => $this->getMessage(),
            'priority' => Priority::Low,
            'subject_type' => $this->appointment->getMorphClass(),
            'subject_id' => $this->appointment->getKey(),
        ];
    }

    protected function getMessage(): string
    {
        return sprintf(
            'You have been invited to %s [%s]',
            $this->appointment->activity_type->name,
            $this->appointment->subject,
        );
    }
}
