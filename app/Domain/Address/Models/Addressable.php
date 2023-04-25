<?php

namespace App\Domain\Address\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Addressable extends Pivot
{
    protected $table = 'addressables';

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo('addressable');
    }
}
