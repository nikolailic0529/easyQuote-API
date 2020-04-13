<?php

namespace App\Http\Resources\Invitation;

use App\Http\Resources\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class InvitationCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return InvitationResource::class;
    }
}
