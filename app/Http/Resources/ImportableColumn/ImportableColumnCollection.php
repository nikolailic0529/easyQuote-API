<?php

namespace App\Http\Resources\ImportableColumn;

use App\Http\Resources\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ImportableColumnCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return ImportableColumnList::class;
    }
}
