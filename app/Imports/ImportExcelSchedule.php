<?php namespace App\Imports;

use App\Models \ {
    QuoteFile\QuoteFile
};
use Maatwebsite\Excel \ {
    Concerns\Importable,
    Concerns\WithMultipleSheets
};

class ImportExcelSchedule implements WithMultipleSheets
{
    use Importable;

    /**
     * QuoteFile Model Instance
     *
     * @var QuoteFile
     */
    protected $quoteFile;

    /**
     * Importable Sheet Index
     *
     * @var int
     */
    protected $importableSheet;

    public function __construct(QuoteFile $quoteFile) {
        $this->quoteFile = $quoteFile;
        $this->importableSheet = $this->quoteFile->imported_page - 1;
    }

    public function sheets(): array
    {
        return [
            $this->importableSheet => new ImportExcelScheduleSheet($this->quoteFile)
        ];
    }
}
