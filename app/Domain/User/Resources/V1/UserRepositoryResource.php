<?php

namespace App\Domain\User\Resources\V1;

use App\Domain\User\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserRepositoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->getKey(),
            'email' => $this->email,
            'team_name' => $this->team_name,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'role_name' => $this->role_name,
            'country_name' => $this->country_name,
            'country_code' => $this->country_code,
            'language' => $this->language,
            'sales_unit_names' => $this->salesUnits->pluck('unit_name')->sort(SORT_NATURAL)->values(),
            'already_logged_in' => (bool) $this->already_logged_in,
            'last_login_at' => $this->last_login_at,
            'activated_at' => $this->activated_at,
        ];
    }
}
