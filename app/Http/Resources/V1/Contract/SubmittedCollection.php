<?php

namespace App\Http\Resources\V1\Contract;

use App\Http\Resources\V1\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SubmittedCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return SubmittedResource::class;
    }
}
