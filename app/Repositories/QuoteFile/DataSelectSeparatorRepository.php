<?php

namespace App\Repositories\QuoteFile;

use App\Contracts\Repositories\QuoteFile\DataSelectSeparatorRepositoryInterface;
use App\Models\QuoteFile\DataSelectSeparator;
use Illuminate\Database\Eloquent\Collection;

class DataSelectSeparatorRepository implements DataSelectSeparatorRepositoryInterface
{
    protected DataSelectSeparator $dataSelectSeparator;

    public function __construct(DataSelectSeparator $dataSelectSeparator)
    {
        $this->dataSelectSeparator = $dataSelectSeparator;
    }

    public function find(string $id)
    {
        return $this->dataSelectSeparator->whereId($id)->first();
    }

    public function findByName(string $name)
    {
        return $this->dataSelectSeparator->whereName($name)->first();
    }

    public function all(): Collection
    {
        return cache()->sear('all-data-select-separators', fn () => $this->dataSelectSeparator->get());
    }
}
