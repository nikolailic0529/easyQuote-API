<?php namespace App\Traits;

use App\Models\Quote\Quote;

trait HasQuotes
{
    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }
}
