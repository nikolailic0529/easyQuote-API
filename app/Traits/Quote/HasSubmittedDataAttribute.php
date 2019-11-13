<?php

namespace App\Traits\Quote;

trait HasSubmittedDataAttribute
{
    public function initializeHasSubmittedDataAttribute()
    {
        $this->casts = array_merge($this->casts, ['submitted_data' => 'array']);
        $this->hidden = array_merge($this->hidden, ['submitted_data']);
    }
}
