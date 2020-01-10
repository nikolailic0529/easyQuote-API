<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class NotificationCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return NotificationResource::class;
    }
}
