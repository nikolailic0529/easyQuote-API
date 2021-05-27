<?php

namespace App\Services\DocumentReaders\Validation;

class RowValuesMatchAnyCombination implements RowValidationPipe
{
    public function __invoke(RowValidationPayload $payload): bool
    {
        foreach ($payload->getRequiredHeaderColumns() as $combination) {
            if (true === $this->matchCombination($combination, $payload->getRowValues())) {
                return true;
            }
        }

        return false;
    }

    protected function matchCombination(array $columnCombination, array $rowValues): bool
    {
        foreach ($columnCombination as $name => $key) {
            if ((false === isset($rowValues[$key])) || trim((string)$rowValues[$key]) === '') {
                return false;
            }
        }

        return true;
    }
}
