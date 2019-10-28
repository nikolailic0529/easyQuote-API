<?php namespace App\Traits\Quote;

use App\Models\Quote\Quote;

trait HasQuotes
{
    public function quotes()
    {
        $this->hasMany(Quote::class);
    }
}
