<?php

namespace App\Http\Resources\Contract;

use App\Http\Resources\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DraftedCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return DraftedResource::class;
    }
}
