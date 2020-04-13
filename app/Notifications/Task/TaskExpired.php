<?php

namespace App\Notifications\Task;

use App\Models\Task;
use App\Notifications\Concerns\OptionalAction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskExpired extends Notification
{
    use Queueable, OptionalAction;

    protected Task $task;

    protected string $taskableName;

    protected ?string $taskableRoute;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Task $task, string $taskableName, ?string $taskableRoute = null)
    {
        $this->task = $task;
        $this->taskableName = $taskableName;
        $this->taskableRoute = $taskableRoute;
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
            'The task "%s" by %s has been expired',
            $this->task->name,
            $this->taskableName
        );

        return tap(
            (new MailMessage)
                ->subject('Task expired')
                ->line($message)
                ->line('Thank you for using our application!'),
            fn ($mailMessage) => $this->optionalAction($mailMessage, $this->taskableName, $this->taskableRoute)
        );
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
