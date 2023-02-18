<?php

namespace App\Domain\User\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\User\Models\User
 */
class UserRepositoryResource extends JsonResource
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
        return [
            'id' => $this->id,
            'email' => $this->email,
            'team_name' => $this->team_name,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'role_name' => $this->role_name,
            'sales_unit_names' => $this->salesUnits->pluck('unit_name')->sort(SORT_NATURAL)->values(),
            'already_logged_in' => (bool) $this->already_logged_in,
            'activated_at' => $this->activated_at,
        ];
    }
}
