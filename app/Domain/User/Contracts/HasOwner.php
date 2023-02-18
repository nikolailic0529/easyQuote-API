<?php

namespace App\Domain\User\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property \App\Domain\User\Models\User|null $owner
 */
interface HasOwner
{
    public function owner(): BelongsTo;
}
