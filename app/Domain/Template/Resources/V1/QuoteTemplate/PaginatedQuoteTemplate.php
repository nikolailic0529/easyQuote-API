<?php

namespace App\Domain\Template\Resources\V1\QuoteTemplate;

use Illuminate\Http\Resources\Json\JsonResource;

class PaginatedQuoteTemplate extends JsonResource
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
        /** @var \App\Domain\Rescue\Models\QuoteTemplate|PaginatedQuoteTemplate $this */

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
            'vendor_names' => $this->vendor_names,
//            'vendor' => [
//                'id' => $this->vendor_id,
//                'name' => $this->vendor_name,
//            ],
            'country_names' => $this->country_names,
            'permissions' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],
            'activated_at' => $this->activated_at,
        ];
    }
}
