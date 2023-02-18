<?php

namespace App\Domain\Maintenance\Notifications;

use App\Domain\Build\Contracts\BuildRepositoryInterface as Builds;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceScheduled extends Notification
{
    use Queueable;

    protected Carbon $startTime;

    protected Carbon $endTime;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Carbon $startTime, Carbon $endTime)
    {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $startInMinutes = max($this->startTime->diffInMinutes(now()->startOfMinute()), 1);
        $unavailableMinutes = $this->startTime->diffInMinutes($this->endTime);

        $pluralMinute = \Str::plural('minute', $startInMinutes);

        $maintenanceMessage = optional(app(Builds::class)->last())->maintenance_message;

        $maintenanceMessage ??= "We are starting maintenance in {$startInMinutes} {$pluralMinute}.\nThe app will be unavailable for {$unavailableMinutes} minutes.";

        return (new MailMessage())
                    ->line("Hi {$notifiable->fullname}")
                    ->line($maintenanceMessage);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
        ];
    }
}
