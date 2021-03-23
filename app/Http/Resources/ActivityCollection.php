<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\TransformsCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ActivityCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return ActivityResource::class;
    }
}
