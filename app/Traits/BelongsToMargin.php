<?php namespace App\Traits;

use App\Models\Quote\Margin\CountryMargin;

trait BelongsToMargin
{
    public function countryMargin()
    {
        return $this->belongsTo(CountryMargin::class)->withDefault(CountryMargin::make([]));
    }
}
