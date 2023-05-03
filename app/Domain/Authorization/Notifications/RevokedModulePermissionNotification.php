<?php

namespace App\Domain\Authorization\Notifications;

use App\Domain\Priority\Enum\Priority;
use App\Domain\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RevokedModulePermissionNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected readonly User $provider,
        protected readonly string $module,
        protected readonly string $level
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

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Granted module access')
            ->greeting("Hi $notifiable->fullname")
            ->line($this->getMessage())
            ->action(config('app.name'), config('app.ui_url'))
            ->line('Thank you for using our application!');
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'message' => $this->getMessage(),
            'priority' => Priority::Low,
        ];
    }

    protected function getMessage(): string
    {
        return sprintf(
            'User [%s] has revoked from you %s access to his own %s.',
            $this->provider->email,
            $this->level,
            $this->module
        );
    }
}
