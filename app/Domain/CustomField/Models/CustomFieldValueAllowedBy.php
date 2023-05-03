<?php

namespace App\Domain\CustomField\Models;

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
