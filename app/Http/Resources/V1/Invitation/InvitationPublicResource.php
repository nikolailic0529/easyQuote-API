<?php

namespace App\Http\Resources\V1\Invitation;

use Illuminate\Http\Resources\Json\JsonResource;

class InvitationPublicResource extends JsonResource
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
            'email' => $this->email,
            'role_name' => $this->role_name
        ];
    }
}
