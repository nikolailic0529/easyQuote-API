<?php namespace App\Traits;

use App\Models\QuoteTemplate\TemplateFieldType;

trait BelongsToTemplateFieldType
{
    public function templateFieldType()
    {
        return $this->belongsTo(TemplateFieldType::class);
    }
}
