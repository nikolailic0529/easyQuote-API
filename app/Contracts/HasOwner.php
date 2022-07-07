<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read User|null $owner
 */
interface HasOwner
{
    public function owner(): BelongsTo;
}