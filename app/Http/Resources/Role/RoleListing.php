<?php

namespace App\Http\Resources\Role;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleListing extends JsonResource
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
            'id' => $this->id,
            'name' => $this->name,
            'users_count' => $this->users_count,
            'permissions' => $this->whenLoaded('permissions'),
            'created_at' => optional($this->created_at)->format(config('date.format_time')),
        ];
    }
}
