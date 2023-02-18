<?php

namespace App\Domain\Template\Concerns;

use App\Domain\Rescue\Models\ContractTemplate;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToContractTemplate
{
    public function contractTemplate(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class)->withTrashed()->withDefault();
    }

    public function getHasContractTemplateAttribute(): bool
    {
        return !is_null($this->contract_template_id);
    }
}
