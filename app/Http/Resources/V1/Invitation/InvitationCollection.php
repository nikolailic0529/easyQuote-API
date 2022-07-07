<?php

namespace App\Http\Resources\V1\Invitation;

use App\Http\Resources\V1\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class InvitationCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return InvitationResource::class;
    }
}
