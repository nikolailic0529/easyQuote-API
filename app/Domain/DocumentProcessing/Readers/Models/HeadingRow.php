<?php

namespace App\Domain\DocumentProcessing\Readers\Models;

final class HeadingRow
{
    private array $mapping;

    private array $missingHeaderMapping;

    private int $sheetIndex;

    private string $sheetName;

    /**
     * HeadingRow constructor.
     *
     * @param array $missingHeadersMapping
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

    public function getMapping(): array
    {
        return $this->mapping;
    }

    public function getSheetIndex(): int
    {
        return $this->sheetIndex;
    }

    public function getSheetName(): string
    {
        return $this->sheetName;
    }

    public function getMissingHeaderMapping(): array
    {
        return $this->missingHeaderMapping;
    }
}
