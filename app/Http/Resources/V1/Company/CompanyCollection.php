<?php

namespace App\Http\Resources\V1\Company;

use App\Http\Resources\V1\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CompanyCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return CompanyList::class;
    }
}
