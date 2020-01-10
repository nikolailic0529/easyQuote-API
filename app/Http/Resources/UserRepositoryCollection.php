<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserRepositoryCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return UserRepositoryResource::class;
    }
}
