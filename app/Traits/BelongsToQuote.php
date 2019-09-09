<?php namespace App\Traits;

use App\Models\Quote\Quote;

trait BelongsToQuote
{
    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }
}