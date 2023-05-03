<?php

namespace App\Domain\Company\Resources\V1;

use App\Domain\Company\Models\Company;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Company
 */
class CompanyList extends JsonResource
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
            'id' => $this->getKey(),
            'user_id' => $this->owner()->getParentKey(),

            'default_vendor_id' => $this->default_vendor_id,
            'default_country_id' => $this->default_country_id,
            'default_template_id' => $this->default_template_id,

            'is_system' => $this->getFlag(Company::SYSTEM),

            'name' => $this->name,
            'short_code' => $this->short_code,
            'type' => $this->type,
            'unit_name' => $this->unit_name,

            'categories' => $this->whenLoaded('categories', fn () => $this->categories->pluck('name')),

            'source' => $this->source,
            'source_long' => __($this->source),

            'vat' => $this->vat,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'logo' => $this->logo,

            'vendors' => $this->whenLoaded('vendors', function () {
                $this->sortVendorsCountries();

                return $this->vendors;
            }),

            'total_quoted_value' => $this->total_quoted_value,

            'default_country' => $this->whenLoaded('defaultCountry'),
            'default_vendor' => $this->whenLoaded('defaultVendor'),
            'default_template' => $this->whenLoaded('defaultTemplate'),

            'addresses' => $this->whenLoaded('addresses'),
            'contacts' => $this->whenLoaded('contacts'),

            'created_at' => optional($this->created_at)->format(config('date.format_time')),
            'activated_at' => $this->activated_at,
        ];
    }
}
