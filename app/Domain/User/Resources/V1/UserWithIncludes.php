<?php

namespace App\Domain\User\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\User\Models\User
 */
class UserWithIncludes extends JsonResource
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
            'id' => $this->getKey(),

            'team_id' => $this->team_id,
            'team' => $this->team,

            'sales_units' => $this->salesUnits,
            'companies' => $this->companies,

            'email' => $this->email,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'user_fullname' => $this->user_fullname,

            'phone' => $this->phone,
            'ip_address' => $this->ip_address,
            'default_route' => $this->default_route,
            'already_logged_in' => $this->already_logged_in,
            'recent_notifications_limit' => $this->recent_notifications_limit,
            'failed_attempts' => $this->failed_attempts,
            'country_id' => $this->country_id,
            'company_id' => $this->company_id,
            'hpe_contract_template_id' => $this->hpe_contract_template_id,
            'timezone_id' => $this->timezone_id,
            'role_id' => $this->role_id,
            'role_name' => $this->role_name,
            'picture' => $this->picture,

            'must_change_password' => $this->must_change_password,
            'timezone_text' => $this->timezone_text,
            'timezone' => $this->timezone,

            'country' => $this->country,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'email_verified_at' => $this->email_verified_at,
            'password_changed_at' => $this->password_changed_at,
            'last_login_at' => isset($this->latestLogin)
                ? $this->latestLogin->{$this->latestLogin->getCreatedAtColumn()}
                : null,
            'activated_at' => $this->activated_at,

            $this->merge($this->additional),
        ];
    }
}
