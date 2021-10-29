<?php

namespace App\Http\Resources\User;

use App\Services\DefaultPermissionBroker;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Facades\Permission;
use App\Models\Role;

class UserByRole extends JsonResource
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
            'role_id'       => $this->role_id,
            'role_name'     => $this->role_name,
            'granted_level' => $this->granted_level,
            'email'         => $this->email,
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
        ];
    }
}
