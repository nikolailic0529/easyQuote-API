<?php

namespace App\Domain\Template\Concerns;

use App\Domain\Template\Models\TemplateField;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTemplateField
{
    public function templateField(): BelongsTo
    {
        return $this->belongsTo(TemplateField::class);
    }
}
