<?php

namespace App\Notifications\WorldwideQuote;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NoteCreated extends Notification
{
    use Queueable;

    protected string $rfqNumber;

    protected string $noteText;

    /**
     * Create a new notification instance.
     *
     * @param string $rfqNumber
     * @param string $noteText
     */
    public function __construct(string $rfqNumber, string $noteText)
    {
        $this->rfqNumber = $rfqNumber;
        $this->noteText = $noteText;
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
        return (new MailMessage)
            ->subject('Created a new quote note')
            ->line("A new note created on the Worldwide Quote RFQ {$this->rfqNumber}")
            ->line($this->noteText)
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
