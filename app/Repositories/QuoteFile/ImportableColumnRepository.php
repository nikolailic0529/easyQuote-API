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

    public function allColumnsRegs()
    {
        $importableColumns = $this->importableColumn->orderBy('order', 'asc')->select('regexp')->get()->toArray();

        return collect($importableColumns)->flatten();
    }

    public function allColumnsAliases()
    {
        $aliases = $this->importableColumn->select('alias')->get()->toArray();

        return collect($aliases)->flatten()->toArray();
    }
}
