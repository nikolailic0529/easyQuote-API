<?php namespace App\Mail;

use App\Models\Collaboration\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Invitation instance
     *
     * @var \App\Models\Collaboration\Invitation
     */
    protected $invitation;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->to($this->invitation->email)
            ->markdown('emails.invitation')
            ->with($this->invitation->toArray());
    }
}
