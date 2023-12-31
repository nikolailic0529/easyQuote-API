<?php

namespace App\Domain\Template\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class TemplateResourceWithIncludes extends JsonResource
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
            'id' => $this->id,
            'name' => $this->name,
            'is_system' => (bool) $this->is_system,
            'company_id' => $this->company_id,
            'vendor_id' => $this->vendor_id,
            'currency_id' => $this->currency_id,
            'business_division_id' => $this->business_division_id,
            'contract_type_id' => $this->contract_type_id,
            'company' => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ],
            'vendor' => [
                'id' => $this->vendor->id,
                'name' => $this->vendor->name,
            ],
            'currency' => $this->currency,
            'form_data' => $this->form_data,
            'data_headers' => Collection::wrap($this->data_headers)->values(),
            'data_headers_keyed' => Collection::wrap($this->data_headers),
            'countries' => $this->countries->map->only('id', 'name'),
            'created_at' => (string) $this->created_at,
            'activated_at' => $this->activated_at,
        ];
    }
}
