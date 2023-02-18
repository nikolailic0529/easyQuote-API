<?php

namespace App\Domain\DocumentProcessing\Readers\Validation;

class RowContainsOnePayMention implements RowValidationPipe, TrueInterruptibleRowValidationPipe
{
    public function __invoke(RowValidationPayload $payload): bool
    {
        foreach ($payload->getRowValues() as $value) {
            if (preg_match('/return to/i', $value)) {
                return true;
            }
        }

        return false;
    }
}
