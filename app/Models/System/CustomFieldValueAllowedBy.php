<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CustomFieldValueAllowedBy extends Pivot
{
    public function allowedByFieldValue(): BelongsTo
    {
        return $this->belongsTo(CustomFieldValue::class);
    }

    public function fieldValue(): BelongsTo
    {
        return $this->belongsTo(CustomFieldValue::class);
    }
}