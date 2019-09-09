<?php namespace App\Repositories\QuoteFile;

use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface;
use App\Models\QuoteFile\ImportableColumn;

class ImportableColumnRepository implements ImportableColumnRepositoryInterface
{
    protected $importableColumn;

    public function __construct(ImportableColumn $importableColumn)
    {
        $this->importableColumn = $importableColumn;
    }

    public function all()
    {
        return $this->importableColumn->ordered()->with('aliases')->get();
    }

    public function allColumnsRegs()
    {
        $importableColumns = $this->importableColumn->ordered()->select('regexp')->get()->toArray();

        return collect($importableColumns)->flatten();
    }

    public function allNames()
    {
        $names = $this->importableColumn->select('name')->get()->toArray();

        return collect($names)->flatten()->toArray();
    }
}
