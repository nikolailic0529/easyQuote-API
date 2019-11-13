<?php

namespace App\Traits\Collaboration;

use App\Models\Collaboration\Invitation;

trait HasInvitations
{
    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }
}
