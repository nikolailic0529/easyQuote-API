<?php

namespace App\Domain\Template\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class TemplateResource extends JsonResource
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
        /** @var \App\Domain\Rescue\Models\QuoteTemplate|TemplateResource $this */

        /** @var \App\Domain\User\Models\User $user */
        $user = $request->user();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_system' => (bool) $this->is_system,
            'company_id' => $this->company_id,
            'vendor_id' => $this->vendor_id,
            'currency_id' => $this->currency_id,
            'company' => [
                'id' => $this->company_id,
                'name' => $this->company_name,
            ],
            'vendor' => [
                'id' => $this->vendor_id,
                'name' => $this->vendor_name,
            ],
            'countries' => $this->countries->map->only(['id', 'name']),
            'permissions' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],
            'created_at' => (string) $this->created_at,
            'activated_at' => (string) $this->activated_at,
        ];
    }
}
