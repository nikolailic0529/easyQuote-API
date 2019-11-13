<?php

namespace App\Traits;

trait HasCountry
{
    public function country()
    {
        return $this->hasOne(App\Country::class);
    }
}
