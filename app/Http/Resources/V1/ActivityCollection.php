<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ActivityCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return ActivityResource::class;
    }
}
