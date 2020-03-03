<?php

namespace App\Traits\Collaboration;

use App\Models\Collaboration\Invitation;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasInvitations
{
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }
}
