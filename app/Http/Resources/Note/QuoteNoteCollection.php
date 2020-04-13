<?php

namespace App\Http\Resources\Note;

use App\Http\Resources\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class QuoteNoteCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return QuoteNoteResource::class;
    }
}
