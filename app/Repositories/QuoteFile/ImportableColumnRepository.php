<?php namespace App\Repositories\QuoteFile;

use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface;
use App\Models\QuoteFile\ImportableColumn;

class ImportableColumnRepository implements ImportableColumnRepositoryInterface
{
    public function allColumnsRegs()
    {
        $importableColumns = ImportableColumn::orderBy('order', 'asc')->select('regexp')->get()->toArray();

        return collect($importableColumns)->flatten();
    }

    public function allColumnsAliases()
    {
        $aliases = ImportableColumn::select('alias')->get()->toArray();

        return collect($aliases)->flatten()->toArray();
    }
}
