<?php namespace App\Traits;

use App\Models\Quote\Margin\CountryMargin;

trait BelongsToMargin
{
    public function countryMargin()
    {
        return $this->belongsTo(CountryMargin::class);
    }

    public function getCountryMarginValueAttribute()
    {
        if(!isset($this->countryMargin)) {
            $this->load('countryMargin');
        }

        return $this->countryMargin->value ?? 0;
    }

    public function createCountryMargin(array $attributes)
    {
        if(!isset($this->user) || !isset($this->vendor) || !isset($this->country)) {
            return null;
        }

        $user = request()->user();

        $this->countryMargin()->dissociate();

        $countryMargin = $user->countryMargins()->quoteAcceptable($this)->firstOrNew(collect($attributes)->except('type')->toArray());

        if($countryMargin->isDirty()) {
            $countryMargin->user()->associate($this->user);
            $countryMargin->country()->associate($this->country);
            $countryMargin->vendor()->associate($this->vendor);
            $countryMargin->save();
        }

        $this->countryMargin()->associate($countryMargin);

        $this->margin_data = collect($countryMargin->only('value', 'method', 'is_fixed'))->put('type', 'By Country')->toArray();

        $this->setAttribute('type', $attributes['quote_type']);
        $this->save();

        return $countryMargin;
    }

    public function deleteCountryMargin()
    {
        $this->countryMargin()->delete();
        $this->countryMargin()->dissociate();

        return $this;
    }
}
