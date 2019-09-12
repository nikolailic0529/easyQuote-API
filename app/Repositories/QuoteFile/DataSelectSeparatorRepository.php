<?php namespace App\Repositories\QuoteFile;

use App\Contracts\Repositories\QuoteFile\DataSelectSeparatorRepositoryInterface;
use App\Models\QuoteFile\DataSelectSeparator;

class DataSelectSeparatorRepository implements DataSelectSeparatorRepositoryInterface
{
    protected $dataSelectSeparator;

    public function __construct(DataSelectSeparator $dataSelectSeparator)
    {
        $this->dataSelectSeparator = $dataSelectSeparator;
    }

    public function find(string $id)
    {
        return $this->dataSelectSeparator->whereId($id)->first();
    }

    public function findBySeparator(string $separator)
    {
        return $this->dataSelectSeparator->whereSeparator($separator)->first();
    }
}