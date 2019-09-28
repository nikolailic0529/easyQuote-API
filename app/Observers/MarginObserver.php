<?php namespace App\Observers;

use App\Models\Quote\Margin\CountryMargin;

class MarginObserver
{
    /**
     * Handle the CountryMargin "creating" event.
     *
     * @param CountryMargin $countryMargin
     * @return void
     */
    public function creating(CountryMargin $countryMargin)
    {
        if($this->exists($countryMargin)) {
            throw new \ErrorException(__('margin.exists_exception'));
        }
    }

    private function exists(CountryMargin $countryMargin)
    {
        $user = $countryMargin->user;

        return $user->countryMargins()
            ->where('quote_type', $countryMargin->quote_type)
            ->where('country_id', $countryMargin->country_id)
            ->where('vendor_id', $countryMargin->vendor_id)
            ->where('is_fixed', $countryMargin->is_fixed)
            ->where('method', $countryMargin->method)
            ->where('value', $countryMargin->value)
            ->exists();
    }
}
