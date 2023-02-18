<?php

namespace App\Domain\DocumentProcessing\Readers\Validation;

class RowIsNotSameAsHeader implements RowValidationPipe
{
    public function __invoke(RowValidationPayload $payload): bool
    {
        $rowValues = array_filter($payload->getRowValues(), function (string $key) use ($payload) {
            return false === in_array($key, $payload->getHeadingRow()->getMissingHeaderMapping(), true);
        }, ARRAY_FILTER_USE_KEY);

        $headingRowValues = array_filter($payload->getHeadingRow()->getMapping(), function (string $key) use ($payload) {
            return false === in_array($key, $payload->getHeadingRow()->getMissingHeaderMapping(), true);
        }, ARRAY_FILTER_USE_KEY);

        $difference = array_diff_assoc($headingRowValues, $rowValues);

        return false === empty($difference);
    }
}
