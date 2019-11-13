<?php

namespace App\Traits\QuoteTemplate;

use App\Models\QuoteTemplate\TemplateField;

trait HasTemplateFields
{
    public function templateFields()
    {
        return $this->hasMany(TemplateField::class);
    }
}
