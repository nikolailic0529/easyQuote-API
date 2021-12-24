<?php

namespace App\Traits;

use App\Models\Template\TemplateField;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTemplateField
{
    public function templateField(): BelongsTo
    {
        return $this->belongsTo(TemplateField::class);
    }
}
