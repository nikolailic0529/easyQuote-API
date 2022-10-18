<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class AuthenticatedUserResource extends JsonResource
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
            'id' => $this->getKey(),
            'email' => $this->email,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,

            'timezone_id' => $this->timezone()->getParentKey(),
            'hpe_contract_template_id' => $this->hpeContractTemplate()->getParentKey(),
            'sales_units' => $this->salesUnits,
            'company_id' => $this->company()->getParentKey(),
            'country_id' => $this->country()->getParentKey(),
            'role_id' => $this->role_id,

            'already_logged_in' => $this->already_logged_in,
            'ip_address' => $this->ip_address,
            'default_route' => $this->default_route,
            'recent_notifications_limit' => $this->recent_notifications_limit,
            'must_change_password' => $this->must_change_password,

            'role_name' => $this->role_name,

            'country' => $this->whenLoaded('country'),
            'picture' => $this->picture,

            'privileges' => $this->privileges,
            'role_properties' => $this->role_properties,

//            'company' => $this->whenLoaded('company'),
            'companies' => $this->whenLoaded('companies'),
            'hpe_contract_template' => $this->whenLoaded('hpeContractTemplate'),

            'led_teams' => $this->ledTeams,

            'build' => [
                'git_tag' => data_get($this->additional, 'build.git_tag'),
                'build_number' => data_get($this->additional, 'build.build_number'),
            ],

            'created_at' => $this->{$this->getCreatedAtColumn()},
            'updated_at' => $this->{$this->getUpdatedAtColumn()},
            'activated_at' => $this->activated_at,
            'last_activity_at' => $this->last_activity_at?->format(config('date.format_time')),
            'last_login_at' => isset($this->latestLogin)
                ? $this->latestLogin->{$this->latestLogin->getCreatedAtColumn()}
                : null,
            'password_changed_at' => $this->password_changed_at?->format(config('date.format_time')),
        ];
    }
}
