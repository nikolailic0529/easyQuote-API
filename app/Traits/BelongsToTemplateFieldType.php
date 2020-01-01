<?php

namespace App\Traits;

use App\Models\QuoteTemplate\TemplateFieldType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTemplateFieldType
{
    public function templateFieldType(): BelongsTo
    {
        return $this->belongsTo(TemplateFieldType::class);
    }
}
