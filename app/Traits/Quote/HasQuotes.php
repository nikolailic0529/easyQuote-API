<?php

namespace App\Traits\Quote;

use App\Models\Quote\Quote;

trait HasQuotes
{
    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }
}
