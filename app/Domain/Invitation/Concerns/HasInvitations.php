<?php

namespace App\Domain\Invitation\Concerns;

use App\Domain\Invitation\Models\Invitation;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasInvitations
{
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }
}
