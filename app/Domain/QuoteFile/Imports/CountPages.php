<?php

namespace App\Domain\QuoteFile\Imports;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Events\BeforeSheet;

class CountPages implements WithEvents, WithLimit
{
    protected int $sheetCount = 0;

    public function getSheetCount(): int
    {
        return $this->sheetCount;
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function () {
                ++$this->sheetCount;
            },
        ];
    }

    public function limit(): int
    {
        return 1;
    }
}
