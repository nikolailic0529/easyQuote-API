<?php

namespace App\Domain\Template\Concerns;

use App\Domain\Template\Models\TemplateFieldType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTemplateFieldType
{
    public function templateFieldType(): BelongsTo
    {
        return $this->belongsTo(TemplateFieldType::class);
    }
}
