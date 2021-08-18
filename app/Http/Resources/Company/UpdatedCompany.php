<?php

namespace App\Http\Resources\Company;

use App\Models\Address;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;

class UpdatedCompany extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var UpdatedCompany|\App\Models\Company $this */

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,

            'default_vendor_id' => $this->default_vendor_id,
            'default_country_id' => $this->default_country_id,
            'default_template_id' => $this->default_template_id,

            'is_system' => $this->is_system,

            'name' => $this->name,
            'short_code' => $this->short_code,
            'type' => $this->type,
            'category' => $this->category,

            'source' => $this->source,
            'source_long' => __($this->source),

            'vat' => $this->vat,
            'vat_type' => $this->vat_type,

            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'logo' => $this->logo,

            'vendors' => value(function () {
                /** @var UpdatedCompany|\App\Models\Company $this */

                $this->prioritizeDefaultCountryOnVendors();

                return $this->vendors;
            }),

            'total_quoted_value'  => $this->total_quoted_value,

            'default_country' => $this->defaultCountry,
            'default_vendor' => $this->defaultVendor,
            'default_template' => $this->defaultTemplate,

            'addresses' => with($this->addresses, function (Collection $addresses) {
                return $addresses
                    ->sortBy('created_at')
                    ->values()
                    ->each(function (Address $address) {
                    $address->setAttribute('is_default', (bool)$address->pivot->is_default);
                    $address->loadMissing('country');
                });
            }),
            'contacts' => with($this->contacts, function (Collection $contacts) {
                return $contacts
                    ->sortBy('created_at')
                    ->values()
                    ->each(function (Contact $contact) {
                    $contact->setAttribute('is_default', (bool)$contact->pivot->is_default);
                });
            }),

            'created_at' => optional($this->created_at)->format(config('date.format_time')),
            'activated_at' => $this->activated_at
        ];
    }
}
