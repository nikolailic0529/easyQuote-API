<?php

namespace App\Http\Resources\Contract;

use App\Http\Resources\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SubmittedCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return SubmittedResource::class;
    }
}
