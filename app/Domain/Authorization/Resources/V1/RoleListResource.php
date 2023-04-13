<?php

namespace App\Domain\Authorization\Resources\V1;

use App\Domain\Authorization\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
final class RoleListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'users_count' => $this->users_count,
            'permissions' => $this->whenLoaded('permissions'),
            'created_at' => $this->created_at?->format(config('date.format_time')),
        ];
    }
}
