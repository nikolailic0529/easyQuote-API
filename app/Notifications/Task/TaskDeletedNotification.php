<?php

namespace App\Notifications\Task;

use App\Models\Task\Task;
use App\Notifications\Concerns\OptionalAction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDeletedNotification extends Notification
{
    use Queueable, OptionalAction;

    public function __construct(public readonly Task $task)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
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
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $message = sprintf(
            'Task has been deleted: `%s`',
            $this->task->name,
        );

        return (new MailMessage)
            ->subject('Task deleted')
            ->line($message)
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
