<?php

namespace App\Traits;

use App\Models\Quote\Margin\CountryMargin;

trait BelongsToMargin
{
    public function countryMargin()
    {
        return $this->belongsTo(CountryMargin::class);
    }

    public function getCountryMarginValueAttribute()
    {
        return $this->countryMargin->value ?? 0;
    }

    public function deleteCountryMargin()
    {
        $freshModel = $this->fresh();
        $freshModel->countryMargin()->dissociate();

        return $freshModel->save();
    }
}
