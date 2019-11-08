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

    public function allSystem()
    {
        return $this->importableColumn->ordered()->system()->with('aliases')->get();
    }

    public function allColumnsRegs()
    {
        $importableColumns = $this->importableColumn->system()->ordered()
            ->select('regexp')->get()->each->makeVisible('regexp')->toArray();

        return collect($importableColumns)->flatten();
    }

    public function allNames()
    {
        $names = $this->importableColumn->system()->select('name')->get()->toArray();

        return collect($names)->flatten()->toArray();
    }

    public function findByName(string $name): ImportableColumn
    {
        return $this->importableColumn->system()->whereName($name)->firstOrFail();
    }
}
