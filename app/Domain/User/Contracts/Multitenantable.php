<?php

namespace App\Domain\User\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface Multitenantable
{
    public function user(): BelongsTo;
}
