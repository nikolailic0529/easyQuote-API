<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResponseResource extends JsonResource
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
            'customer_name' => $this->name,
            'rfq_number' => $this->rfq,
            'quotation_valid_until' => $this->valid_until_date,
            'support_start_date' => $this->support_start_date,
            'support_end_date' => $this->support_end_date,
            'country' => $this->country_code,
            'country_id' => $this->country_id,
            'service_levels' => isset($this->service_levels) ? $this->service_levels->toArray() : [],
            'invoicing_terms'  => $this->invoicing_terms,
            'addresses' => $this->addresses->map(function ($address) {
                return [
                    'address_type' => $address->address_type,
                    'address_1' => $address->address_1,
                    'address_2' => $address->address_2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'post_code' => $address->post_code,
                    'country_code' => $address->country_code,
                    'contact_name' => $address->contact_name,
                    'contact_number' => $address->contact_number
                ];
            })->toArray()
        ];
    }
}
