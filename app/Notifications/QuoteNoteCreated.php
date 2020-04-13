<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use App\Models\Quote\QuoteNote;

class QuoteNoteCreated extends Notification
{
    use Queueable;

    protected QuoteNote $quoteNote;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(QuoteNote $quoteNote)
    {
        $this->quoteNote = $quoteNote;
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
        $causer = $this->quoteNote->user;
        $quote = $this->quoteNote->quote;

        $message = sprintf(
            'User %s created a new note on the Quote RFQ %s.',
            $causer->email,
            $quote->customer->rfq,
        );

        return (new MailMessage)
            ->subject('Created a new quote note')
            ->line($message)
            ->line($this->quoteNote->text)
            ->action('Quotation', ui_route('quotes.status', compact('quote')))
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
