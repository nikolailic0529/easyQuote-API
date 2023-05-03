<?php

namespace App\Domain\Invitation\Observers;

use App\Domain\Invitation\Mail\InvitationMail;
use App\Domain\Invitation\Models\Invitation;
use Illuminate\Support\Facades\Mail;

class InvitationObserver
{
    /**
     * Handle the invitation "created" event.
     *
     * @return void
     */
    public function created(Invitation $invitation)
    {
        Mail::send(new InvitationMail($invitation));
    }

    /**
     * Handle the invitation "resended" event.
     *
     * @return void
     */
    public function resended(Invitation $invitation)
    {
        Mail::send(new InvitationMail($invitation));

        activity()
            ->performedOn($invitation)
            ->queue('resended');
    }

    /**
     * Handle the invitation "canceled" event.
     *
     * @return void
     */
    public function canceled(Invitation $invitation)
    {
        activity()
            ->performedOn($invitation)
            ->queue('canceled');
    }
}
