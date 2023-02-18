<?php

namespace App\Domain\DocumentProcessing\Readers\Models;

final class Row
{
    private HeadingRow $headingRow;

    private array $rowValues;

    /**
     * Row constructor.
     *
     * @param \App\Domain\DocumentProcessing\Readers\Models\HeadingRow $headingRow
     */
    public function __construct(HeadingRow $headingRow, array $rowValues)
    {
        $this->headingRow = $headingRow;
        $this->rowValues = $rowValues;
    }

    /**
     * @return \App\Domain\DocumentProcessing\Readers\Models\HeadingRow
     */
    public function getHeadingRow(): HeadingRow
    {
        return $this->headingRow;
    }

    public function getRowValues(): array
    {
        return $this->rowValues;
    }
}
