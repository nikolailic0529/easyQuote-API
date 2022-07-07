<?php

namespace App\Http\Resources\V1\QuoteRepository;

use App\Http\Resources\V1\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DraftedCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return DraftedResource::class;
    }
}
