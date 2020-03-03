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

        $nullValues = array_map(fn ($value) => ($value === 'null') ? null : $value, $this->only($this->nullValues()));

        $this->merge($nullValues);
    }
}
