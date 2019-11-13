<?php

namespace App\Imports;

use Maatwebsite\Excel\{
    Concerns\WithEvents,
    Concerns\WithChunkReading,
    Events\BeforeSheet,
};

class CountPages implements WithEvents, WithChunkReading
{
    protected $sheetCount;

    public function __construct()
    {
        $this->sheetCount = 0;
    }

    public function getSheetCount(): int
    {
        return $this->sheetCount;
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function () {
                $this->sheetCount++;
            }
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
