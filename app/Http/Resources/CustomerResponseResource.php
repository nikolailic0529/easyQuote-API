<?php

namespace App\Http\Resources;

use App\Models\Address;
use App\Models\Customer\Customer;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

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
        /** @var Customer|CustomerResponseResource $this */

        return [
            'customer_name'         => $this->name,
            'rfq_number'            => $this->rfq,
            'quotation_valid_until' => Carbon::parse($this->getRawOriginal('valid_until'))->format('m/d/Y'),
            'support_start_date'    => Carbon::parse($this->getRawOriginal('support_start'))->format('m/d/Y'),
            'support_end_date'      => Carbon::parse($this->getRawOriginal('support_end'))->format('m/d/Y'),
            'country'               => $this->country_code,
            'country_id'            => $this->country_id,
            'service_levels'        => isset($this->service_levels) ? $this->service_levels->toArray() : [],
            'invoicing_terms'       => $this->invoicing_terms,
            'addresses'             => $this->addresses->map(function (Address $address) {
                return [
                    'address_type'      => $address->address_type,
                    'address_1'         => $address->address_1,
                    'address_2'         => $address->address_2,
                    'country_code'      => $address->country?->iso_3166_2,
                    'city'              => $address->city,
                    'state'             => $address->state,
                    'post_code'         => $address->post_code,
                    'contact_name'      => $address->contact_name,
                    'contact_number'    => $address->contact_number,
                    'contact_email'     => $address->contact_email,
                ];
            })->toArray()
        ];
    }
}
