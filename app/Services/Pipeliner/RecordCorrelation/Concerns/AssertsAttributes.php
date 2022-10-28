<?php

namespace App\Services\Pipeliner\RecordCorrelation\Concerns;

use App\Services\Pipeliner\RecordCorrelation\Exceptions\RecordCorrelationException;

trait AssertsAttributes
{
    /**
     * @throws RecordCorrelationException
     */
    public function assertAttributePresent(string $attribute, array $item, array $another): void
    {
        $items = [$item, $another];

        foreach ($items as $item) {
            if (!key_exists($attribute, $item)) {
                throw new RecordCorrelationException("Both comparable items must have [$attribute].");
            }
        }
    }
}