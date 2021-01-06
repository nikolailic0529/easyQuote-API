<?php

namespace App\Traits\QuoteTemplate;

use App\Models\Template\TemplateField;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasTemplateFields
{
    public function templateFields(): HasMany
    {
        return $this->hasMany(TemplateField::class);
    }
}
