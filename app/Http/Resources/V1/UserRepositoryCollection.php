<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserRepositoryCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return UserRepositoryResource::class;
    }
}
