<?php

namespace App\Domain\DocumentProcessing\Readers\Validation;

class RowContainsOnlySerialNumberMention implements RowValidationPipe, TrueInterruptibleRowValidationPipe
{
    public function __invoke(RowValidationPayload $payload): bool
    {
        $rowValues = array_filter($payload->getRowValues(), fn ($value) => is_null($value) || trim($value) !== '');

        if (count($rowValues) > 1) {
            return false;
        }

        foreach ($rowValues as $value) {
            if (str_contains(mb_strtolower($value), 'serial number:')) {
                return true;
            }
        }

        return false;
    }
}
