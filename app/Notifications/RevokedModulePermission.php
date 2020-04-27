<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RevokedModulePermission extends Notification
{
    use Queueable;

    protected User $provider;

    protected string $module, $level;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $provider, string $module, string $level)
    {
        $this->provider = $provider;
        $this->module = $module;
        $this->level = $level;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $message = sprintf(
            'User %s has revoked from you %s access to his own %s.',
            $this->provider->email,
            $this->level,
            $this->module
        );

        return (new MailMessage)
            ->subject('Granted module access')
            ->greeting("Hi {$notifiable->fullname}")
            ->line($message)
            ->action(config('app.name'), config('app.ui_url'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
