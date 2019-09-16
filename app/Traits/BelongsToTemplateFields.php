<?php namespace App\Traits;

use App\Models\QuoteTemplate\TemplateField;

trait BelongsToTemplateFields
{
    public function templateFields()
    {
        return $this->belongsToMany(TemplateField::class);
    }
}
