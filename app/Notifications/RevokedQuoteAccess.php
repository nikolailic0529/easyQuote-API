<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\{
    User,
    Quote\Quote,
};

class RevokedQuoteAccess extends Notification
{
    use Queueable;

    protected User $causer;

    protected Quote $quote;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $causer, Quote $quote)
    {
        $this->causer = $causer;
        $this->quote = $quote;
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
            'User %s has revoked your access to Quote RFQ %s',
            optional($this->causer)->email,
            optional($this->quote->customer)->rfq
        );

        return (new MailMessage)
                    ->line($message)
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
