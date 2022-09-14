<?php

namespace App\Http\Resources\V1\Role;

use Illuminate\Http\Resources\Json\JsonResource;

class Role extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'user_id'       => $this->user_id,
            'name'          => $this->name,
            'privileges'    => $this->privileges,
            'properties'    => $this->properties,
            'is_system'     => (bool) $this->is_system,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
            'activated_at'  => $this->activated_at,
        ];
    }
}
