<?php

namespace App\Domain\Invitation\Resources\V1;

use App\Domain\Invitation\Models\Invitation;
use App\Domain\User\Resources\V1\UserRelationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Invitation
 */
class InvitationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'role_id' => $this->role_id,
            'issuer' => UserRelationResource::make($this->user),
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
