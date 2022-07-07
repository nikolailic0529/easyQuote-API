<?php

namespace App\Http\Resources\V1\SalesOrderTemplate;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderTemplateList extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Models\Template\SalesOrderTemplate|\App\Http\Resources\SalesOrderTemplate\SalesOrderTemplateList $this */

        /** @var \App\Models\User $user */
        $user = $request->user();

        return [
            'id' => $this->getKey(),
            'user_id' => $this->user_id,
            'name' => $this->name,
            'company_name' => $this->company_name,
            'vendor_name' => $this->vendor_name,
            'country_names' => $this->country_names,
            'is_system' => $this->is_system,

            'permissions' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],

            'created_at' => $this->created_at,
            'activated_at' => $this->activated_at,
        ];
    }
}
