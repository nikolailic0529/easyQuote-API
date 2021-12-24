<?php

namespace App\Services\DocumentReaders\Validation;

use App\Services\DocumentReaders\Models\HeadingRow;

final class RowValidationPayload
{
    protected HeadingRow $headingRow;
    protected array $rowValues;
    protected array $requiredHeaderColumns;

    /**
     * RowValidationPayload constructor.
     * @param \App\Services\DocumentReaders\Models\HeadingRow $headingRow
     * @param array $rowValues
     * @param array $requiredHeaderColumns
     */
    public function __construct(HeadingRow $headingRow, array $rowValues, array $requiredHeaderColumns)
    {
        $this->headingRow = $headingRow;
        $this->rowValues = $rowValues;
        $this->requiredHeaderColumns = $requiredHeaderColumns;
    }

    /**
     * @return array
     */
    public function getRowValues(): array
    {
        return $this->rowValues;
    }

    /**
     * @return \App\Services\DocumentReaders\Models\HeadingRow
     */
    public function getHeadingRow(): HeadingRow
    {
        return $this->headingRow;
    }

    /**
     * @return array
     */
    public function getRequiredHeaderColumns(): array
    {
        return $this->requiredHeaderColumns;
    }
}
