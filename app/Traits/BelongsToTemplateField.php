<?php

namespace App\Traits;

use App\Models\QuoteTemplate\TemplateField;

trait BelongsToTemplateField
{
    public function templateField()
    {
        return $this->belongsTo(TemplateField::class);
    }
}
