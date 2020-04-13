<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface Multitenantable
{
    public function user(): BelongsTo;
}