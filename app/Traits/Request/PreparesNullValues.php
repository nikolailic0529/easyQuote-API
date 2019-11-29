<?php

namespace App\Traits\Request;

trait PreparesNullValues
{
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if (!method_exists($this, 'nullValues')) {
            return;
        }

        $prepared = collect($this->only($this->nullValues()))
            ->transform(function ($value) {
                return $value === 'null' ? null : $value;
            })->toArray();

        $this->merge($prepared);
    }
}
