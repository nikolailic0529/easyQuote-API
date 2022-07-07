<?php

namespace App\Http\Resources\V1\User;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UserRoleGroup extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $first = $this->collection->first();

        $selected = $this->collection->contains('granted_level', '!=', null);

        return [
            'role_id' => $first->role_id,
            'role_name' => $first->role_name,
            'is_selected' => $selected,
            'users' => $this->collection,
        ];
    }
}
