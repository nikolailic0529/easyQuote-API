<?php

namespace App\Domain\Invitation\Mail;

use App\Domain\Invitation\Models\Invitation;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use SerializesModels;

    /**
     * Invitation instance.
     *
     * @var \App\Domain\Invitation\Models\Invitation
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
            ->subject(__('mail.subjects.invitation'))
            ->markdown('emails.invitation')
            ->with($this->invitation->toArray());
    }
}
