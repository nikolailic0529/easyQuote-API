<?php

namespace App\Http\Resources\Company;

use App\Http\Resources\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CompanyCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return CompanyList::class;
    }
}
