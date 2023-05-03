<?php

namespace App\Domain\DocumentProcessing\Readers\Validation;

class RowContainsOnlySubtotal implements RowValidationPipe
{
    public function __invoke(RowValidationPayload $payload): bool
    {
        $rowValues = array_filter($payload->getRowValues(), fn ($value) => is_null($value) || trim($value) !== '');

        if (count($rowValues) !== 2) {
            return true;
        }

        $subtotalMatch = value(function () use ($rowValues) {
            foreach ($rowValues as $key => $value) {
                if (str_contains(mb_strtolower($value), 'subtotal')) {
                    return $key;
                }
            }

            return false;
        });

        if (false === $subtotalMatch) {
            return true;
        }

        unset($rowValues[$subtotalMatch]);

        $priceValue = array_shift($rowValues);

        if (is_numeric($priceValue)) {
            return false;
        }

        return true;
    }
}
