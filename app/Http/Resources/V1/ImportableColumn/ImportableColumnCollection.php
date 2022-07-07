<?php

namespace App\Http\Resources\V1\ImportableColumn;

use App\Http\Resources\V1\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ImportableColumnCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return ImportableColumnList::class;
    }
}
