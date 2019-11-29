<?php

namespace App\Traits\Discount;

trait HasValueAttribute
{
    public function initializeHasValueAttribute()
    {
        $this->fillable = array_merge($this->fillable, ['value']);
        $this->casts = array_merge($this->casts, ['value' => 'decimal,2']);
    }
}
