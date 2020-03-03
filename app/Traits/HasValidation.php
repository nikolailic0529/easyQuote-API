<?php

namespace App\Traits;

trait HasValidation
{
    public function initializeHasValidation()
    {
        $this->fillable = array_merge($this->fillable, ['validation']);
        $this->casts = array_merge($this->casts, ['validation' => 'array']);
    }
}
