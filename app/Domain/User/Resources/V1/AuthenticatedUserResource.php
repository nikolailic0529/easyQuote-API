<?php

namespace App\Domain\User\Resources\V1;

use App\Domain\Company\Models\Company;
use App\Domain\HpeContract\Models\HpeContractTemplate;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\User\Models\User
 */
class AuthenticatedUserResource extends JsonResource
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
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'language' => $this->language,

            'timezone_id' => $this->timezone()->getParentKey(),
            'timezone' => $this->timezone,
            'hpe_contract_template_id' => $this->hpeContractTemplate()->getParentKey(),
            'sales_units' => $this->salesUnits,
            'company_id' => $this->company()->getParentKey(),
            'country_id' => $this->country()->getParentKey(),
            'role_id' => $this->role_id,

            'already_logged_in' => $this->already_logged_in,
            'ip_address' => $this->ip_address,
            'default_route' => $this->default_route,
            'must_change_password' => $this->must_change_password,
            'role_name' => $this->role_name,

            'recent_notifications_limit' => $this->recent_notifications_limit,

            'country' => $this->whenLoaded('country'),
            'picture' => $this->picture,

            'companies' => $this->companies->map(static function (Company $company): array {
                return [
                    'id' => $company->getKey(),
                    'name' => $company->name,
                ];
            }),
            'hpe_contract_template' => transform($this->hpeContractTemplate,
                static function (HpeContractTemplate $template): array {
                    return [
                        'id' => $template->getKey(),
                        'name' => $template->name,
                    ];
                }),

            'led_teams' => $this->ledTeams,

            'created_at' => $this->{$this->getCreatedAtColumn()},
            'updated_at' => $this->{$this->getUpdatedAtColumn()},
            'activated_at' => $this->activated_at,
            'last_activity_at' => $this->last_activity_at?->format(config('date.format_time')),
            'last_login_at' => isset($this->latestLogin)
                ? $this->latestLogin->{$this->latestLogin->getCreatedAtColumn()}
                : null,
            'password_changed_at' => $this->password_changed_at?->format(config('date.format_time')),

            $this->merge($this->additional),
        ];
    }
}
