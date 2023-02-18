<?php

namespace App\Domain\Template\Concerns;

use App\Domain\Template\Models\TemplateField;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasTemplateFields
{
    public function templateFields(): HasMany
    {
        return $this->hasMany(TemplateField::class);
    }
}
