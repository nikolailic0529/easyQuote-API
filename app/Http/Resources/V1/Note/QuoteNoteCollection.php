<?php

namespace App\Http\Resources\V1\Note;

use App\Http\Resources\V1\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class QuoteNoteCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return QuoteNoteResource::class;
    }
}
