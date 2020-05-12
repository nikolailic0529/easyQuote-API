<?php

namespace App\Http\Resources\Company;

use Illuminate\Http\Resources\Json\JsonResource;

class Company extends JsonResource
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
            'id' => $this->id,

            'user_id' => $this->user_id,
            
            'default_vendor_id' => $this->default_vendor_id,
            'default_country_id' => $this->default_country_id,
            'default_template_id' => $this->default_template_id,
            
            'name' => $this->name,
            'short_code' => $this->short_code,
            'type' => $this->type,
            'category' => $this->category,
            'vat' => $this->vat,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'logo' => $this->logo,

            'vendors' => $this->whenLoaded('vendors', function () {
                $this->sortVendorsCountries();

                return $this->vendors;
            }),
            
            'addresses' => $this->whenLoaded('addresses'),
            'contacts' => $this->whenLoaded('contacts'),
            
            'created_at' => optional($this->created_at)->format(config('date.format_time')),
            'activated_at' => $this->activated_at
        ];
    }
}
