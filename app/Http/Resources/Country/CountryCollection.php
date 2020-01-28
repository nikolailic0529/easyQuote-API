<?php

namespace App\Http\Resources\Country;

use App\Http\Resources\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CountryCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return CountryResource::class;
    }
}
