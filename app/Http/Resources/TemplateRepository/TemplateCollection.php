<?php

namespace App\Http\Resources\TemplateRepository;

use App\Http\Resources\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TemplateCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return TemplateResource::class;
    }
}
