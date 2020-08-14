<?php

namespace App\Http\Resources\TemplateRepository;

use Illuminate\Http\Resources\Json\JsonResource;

class TemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Models\User */
        $user = $request->user();

        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'is_system'         => (bool) $this->is_system,
            'company_id'        => $this->company_id,
            'vendor_id'         => $this->vendor_id,
            'currency_id'       => $this->currency_id,
            'company'           => [
                'id'    => $this->company->id,
                'name'  => $this->company->name
            ],
            'vendor'            => [
                'id'    => $this->vendor->id,
                'name'  => $this->vendor->name
            ],
            'countries'         => $this->countries->map->only('id', 'name'),
            'permissions'       => [
                'view'      => $user->can('view', $this->resource),
                'update'    => $user->can('update', $this->resource),
                'delete'    => $user->can('delete', $this->resource),
            ],
            'created_at'        => (string) $this->created_at,
            'activated_at'      => (string) $this->activated_at
        ];
    }
}
