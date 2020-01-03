<?php

namespace App\Repositories\QuoteFile;

use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface;
use App\Models\QuoteFile\ImportableColumn;
use Closure;

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

    public function allNames()
    {
        $names = $this->importableColumn->system()->select('name')->get()->toArray();

        return collect($names)->flatten()->toArray();
    }

    public function findByName(string $name): ImportableColumn
    {
        return $this->importableColumn->system()->whereName($name)->firstOrFail();
    }

    public function firstOrCreate(array $attributes, array $values = [], ?Closure $scope = null): ImportableColumn
    {
        $query = $this->importableColumn->query();

        if ($scope instanceof Closure) {
            call_user_func($scope, $query);
        }

        return $query->firstOrCreate($attributes, $values);
    }
}
