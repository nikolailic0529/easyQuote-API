<?php

namespace App\Services\DocumentReaders\Models;

final class Row
{
    protected HeadingRow $headingRow;

    protected array $rowValues;

    /**
     * Row constructor.
     * @param \App\Services\DocumentReaders\Models\HeadingRow $headingRow
     * @param array $rowValues
     */
    public function __construct(HeadingRow $headingRow, array $rowValues)
    {
        $this->headingRow = $headingRow;
        $this->rowValues = $rowValues;
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
    public function getRowValues(): array
    {
        return $this->rowValues;
    }
}
