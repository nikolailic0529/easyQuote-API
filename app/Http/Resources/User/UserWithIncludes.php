<?php

namespace App\Http\Resources\User;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class UserWithIncludes extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var User|UserWithIncludes $this */

        return [
            'id' => $this->id,
            'team_id' => $this->team_id,

            'team' => $this->team,

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

            'privileges' => $this->privileges,
            'role_properties' => $this->role_properties,
            'must_change_password' => $this->must_change_password,
            'timezone_text' => $this->timezone_text,
            'timezone' => $this->timezone,


            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'email_verified_at' => $this->email_verified_at,
            'password_changed_at' => $this->password_changed_at,
            'activated_at' => $this->activated_at,
        ];
    }
}
