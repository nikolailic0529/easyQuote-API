<?php

namespace App\Domain\Rescue\Quote;

use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasContract
{
    public function contract(): HasOne
    {
        return $this->hasOne(\App\Domain\Rescue\Models\Contract::class)->withDefault();
    }

    public function getHasContractAttribute(): bool
    {
        return $this->contract->exists;
    }

    public function getContractSubmittedAttribute(): bool
    {
        return !is_null($this->contract->submitted_at);
    }
}
