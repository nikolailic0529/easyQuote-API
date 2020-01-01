<?php

namespace App\Traits\Request;

trait PreparesNullValues
{
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->prepareNullValues();
    }

    protected function prepareNullValues(): void
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
