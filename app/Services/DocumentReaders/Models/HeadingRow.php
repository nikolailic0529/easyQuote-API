<?php

namespace App\Services\DocumentReaders\Models;

final class HeadingRow
{
    protected array $mapping;

    protected array $missingHeaderMapping;

    protected int $sheetIndex;

    protected string $sheetName;


    /**
     * HeadingRow constructor.
     * @param array $mapping
     * @param array $missingHeadersMapping
     * @param int $sheetIndex
     * @param string $sheetName
     */
    public function __construct(array $mapping,
                                array $missingHeaderMapping,
                                int $sheetIndex,
                                string $sheetName)
    {
        $this->mapping = $mapping;
        $this->missingHeaderMapping = $missingHeaderMapping;
        $this->sheetIndex = $sheetIndex;
        $this->sheetName = $sheetName;
    }

    /**
     * @return array
     */
    public function getMapping(): array
    {
        return $this->mapping;
    }

    /**
     * @return int
     */
    public function getSheetIndex(): int
    {
        return $this->sheetIndex;
    }

    /**
     * @return string
     */
    public function getSheetName(): string
    {
        return $this->sheetName;
    }

    /**
     * @return array
     */
    public function getMissingHeaderMapping(): array
    {
        return $this->missingHeaderMapping;
    }
}
