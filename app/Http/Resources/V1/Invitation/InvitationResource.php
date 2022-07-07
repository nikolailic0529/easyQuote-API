<?php

namespace App\Http\Resources\V1\Invitation;

use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
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
            'user_id' => $this->user_id,
            'role_id' => $this->role_id,
            'email' => $this->email,
            'role_name' => $this->whenLoaded('role', fn () => $this->role->name),
            'invitation_token' => $this->invitation_token,
            'host' => $this->host,
            'created_at' => optional($this->created_at)->format(config('date.format_time')),
            'expires_at' => optional($this->expires_at)->format(config('date.format_time')),
            'is_expired' => (bool) $this->is_expired,
        ];
    }
}
