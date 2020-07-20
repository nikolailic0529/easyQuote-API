<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

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
        $build = optional(optional($this->additional)['build']);

        return [
            'id'                            => $this->id,
            'email'                         => $this->email,
            'first_name'                    => $this->first_name,
            'middle_name'                   => $this->middle_name,
            'last_name'                     => $this->last_name,
            'phone'                         => $this->phone,
            'timezone_id'                   => $this->timezone_id,
            'already_logged_in'             => $this->already_logged_in,
            'ip_address'                    => $this->ip_address,
            'default_route'                 => $this->default_route,
            'recent_notifications_limit'    => $this->recent_notifications_limit,
            'must_change_password'          => $this->must_change_password,
            'role_id'                       => $this->role_id,
            'role_name'                     => $this->role_name,
            'country_id'                    => $this->country_id,
            'country'                       => $this->whenLoaded('country'),
            'picture'                       => $this->picture,
            'privileges'                    => $this->privileges,
            'role_properties'               => $this->role_properties,
            'build'                         => [
                'git_tag'       => $build->git_tag,
                'build_number'  => $build->build_number
            ],
            'created_at'                    => $this->created_at,
            'activated_at'                  => $this->activated_at,
            'last_activity_at'              => optional($this->last_activity_at)->format(config('date.format_time')),
            'password_changed_at'           => optional($this->password_changed_at)->format(config('date.format_time'))
        ];
    }
}
