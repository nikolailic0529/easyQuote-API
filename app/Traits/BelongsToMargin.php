<?php

namespace App\Traits;

use App\Models\Quote\Margin\CountryMargin;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToMargin
{
    public function countryMargin(): BelongsTo
    {
        return $this->belongsTo(CountryMargin::class);
    }

    public function getCountryMarginValueAttribute(): float
    {
        return $this->countryMargin->value ?? 0;
    }

    public function deleteCountryMargin(): bool
    {
        $freshModel = $this->fresh();
        $freshModel->countryMargin()->dissociate();

        return $freshModel->save();
    }
}
