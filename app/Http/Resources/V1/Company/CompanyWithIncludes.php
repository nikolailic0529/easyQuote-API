<?php

namespace App\Http\Resources\V1\Company;

use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Company
 */
class CompanyWithIncludes extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->getKey(),
            'user_id' => $this->owner()->getParentKey(),
            'sales_unit_id' => $this->salesUnit()->getParentKey(),

            'default_vendor_id' => $this->defaultVendor()->getParentKey(),
            'default_country_id' => $this->defaultCountry()->getParentKey(),
            'default_template_id' => $this->defaultTemplate()->getParentKey(),

            'is_system' => $this->getFlag(Company::SYSTEM),
            'is_source_frozen' => $this->getFlag(Company::FROZEN_SOURCE),

            'name' => $this->name,
            'short_code' => $this->short_code,
            'type' => $this->type,
            'customer_type' => $this->customer_type,

            'source' => $this->source,
            'source_long' => __($this->source),

            'vat' => $this->vat,
            'vat_type' => $this->vat_type,

            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'logo' => $this->logo,

            'categories' => $this->categories->pluck('name'),

            'vendors' => value(function () {
                /** @var CompanyWithIncludes|\App\Models\Company $this */
                $this->prioritizeDefaultCountryOnVendors();

                return $this->vendors;
            }),

            'total_quoted_value' => $this->total_quoted_value,

            'default_country' => $this->defaultCountry,
            'default_vendor' => $this->defaultVendor,
            'default_template' => $this->defaultTemplate,
            'sales_unit' => $this->salesUnit,

            'addresses' => with($this->addresses, function (Collection $addresses) {
                return $addresses
                    ->sortBy('created_at')
                    ->values()
                    ->each(function (Address $address): void {
                        $address->setAttribute('is_default', (bool)$address->pivot->is_default);
                        $address->loadMissing('country');
                        $address->setAttribute('contact_id',
                            $this->contacts
                                ->first(static fn(Contact $contact): bool => $contact->address()->is($address))
                                ?->getKey()
                        );
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

            'permissions' => [
                'view' => $request->user()->can('view', $this->resource),
                'update' => $request->user()->can('update', $this->resource),
                'delete' => $request->user()->can('delete', $this->resource),
            ],

            'created_at' => $this->{$this->getCreatedAtColumn()}?->format(config('date.format_time')),
            'activated_at' => $this->activated_at,
        ];
    }
}
