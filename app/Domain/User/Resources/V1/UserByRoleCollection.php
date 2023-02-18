<?php

namespace App\Domain\User\Resources\V1;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UserByRoleCollection extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->groupBy(fn ($user) => optional($user->role)->id)
            ->mapInto(UserRoleGroup::class)
            ->values();
    }
}
