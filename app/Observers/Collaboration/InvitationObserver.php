<?php

namespace App\Observers\Collaboration;

use App\Mail\InvitationMail;
use App\Models\Collaboration\Invitation;
use Mail;

class InvitationObserver
{
    /**
     * Handle the invitation "created" event.
     *
     * @param  \App\Models\Collaboration\Invitation  $invitation
     * @return void
     */
    public function created(Invitation $invitation)
    {
        Mail::send(new InvitationMail($invitation));
    }

    /**
     * Handle the invitation "resended" event.
     *
     * @param Invitation $invitation
     * @return void
     */
    public function resended(Invitation $invitation)
    {
        Mail::send(new InvitationMail($invitation));
    }
}
