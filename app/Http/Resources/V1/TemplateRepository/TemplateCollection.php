<?php

namespace App\Http\Resources\V1\TemplateRepository;

use App\Http\Resources\V1\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TemplateCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return TemplateResource::class;
    }
}
