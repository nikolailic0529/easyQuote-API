<?php

namespace App\Domain\QuoteFile\Imports;

use App\Domain\QuoteFile\Models\QuoteFile;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImportExcelSchedule implements WithMultipleSheets
{
    use Importable;

    /**
     * QuoteFile Model Instance.
     *
     * @var \App\Domain\QuoteFile\Models\QuoteFile
     */
    protected $quoteFile;

    /**
     * Importable Sheet Index.
     *
     * @var int
     */
    protected $importableSheet;

    public function __construct(QuoteFile $quoteFile)
    {
        $this->quoteFile = $quoteFile;
        $this->importableSheet = $this->quoteFile->imported_page - 1;
    }

    public function sheets(): array
    {
        return [
            $this->importableSheet => new ImportExcelScheduleSheet($this->quoteFile),
        ];
    }
}
