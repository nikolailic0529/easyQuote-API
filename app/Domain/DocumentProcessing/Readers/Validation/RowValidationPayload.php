<?php

namespace App\Domain\DocumentProcessing\Readers\Validation;

use App\Domain\DocumentProcessing\Readers\Models\HeadingRow;

final class RowValidationPayload
{
    private HeadingRow $headingRow;
    private array $rowValues;
    private array $requiredHeaderColumns;

    /**
     * RowValidationPayload constructor.
     */
    public function __construct(HeadingRow $headingRow, array $rowValues, array $requiredHeaderColumns)
    {
        $this->headingRow = $headingRow;
        $this->rowValues = $rowValues;
        $this->requiredHeaderColumns = $requiredHeaderColumns;
    }

    public function getRowValues(): array
    {
        return $this->rowValues;
    }

    public function getHeadingRow(): HeadingRow
    {
        return $this->headingRow;
    }

    public function getRequiredHeaderColumns(): array
    {
        return $this->requiredHeaderColumns;
    }
}
