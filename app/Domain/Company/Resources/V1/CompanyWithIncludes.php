<?php

namespace App\Domain\Company\Resources\V1;

use App\Domain\Address\Models\Address;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\User\Resources\V1\UserRelationResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->getKey(),
            'user_id' => $this->owner()->getParentKey(),
            'user' => UserRelationResource::make($this->owner),
            'sharing_users' => UserRelationResource::collection($this->sharingUsers),
            'sales_unit_id' => $this->salesUnit()->getParentKey(),

            'default_vendor_id' => $this->defaultVendor()->getParentKey(),
            'default_country_id' => $this->defaultCountry()->getParentKey(),
            'default_template_id' => $this->defaultTemplate()->getParentKey(),

            'is_system' => $this->getFlag(Company::SYSTEM),
            'is_source_frozen' => $this->getFlag(Company::FROZEN_SOURCE),

            'status' => $this->status,
            'status_name' => $this->status->name,

            'registered_number' => $this->registered_number,
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
            'employees_number' => $this->employees_number,
            'logo' => filled($this->logo) ? $this->logo : null,

            'categories' => $this->categories->pluck('name'),
            'industries' => $this->industries,

            'vendors' => value(function () {
                /* @var CompanyWithIncludes|\App\Domain\Company\Models\Company $this */
                $this->prioritizeDefaultCountryOnVendors();

                return $this->vendors;
            }),

            'total_quoted_value' => $this->total_quoted_value,

            'default_country' => $this->defaultCountry,
            'default_vendor' => $this->defaultVendor,
            'default_template' => $this->defaultTemplate,
            'sales_unit' => $this->salesUnit,

            'addresses' => with($this->addresses, function (Collection $addresses): AnonymousResourceCollection {
                $collection = $addresses
                    ->loadMissing(['country', 'user'])
                    ->sortBy('created_at')
                    ->each(function (Address $address): void {
                        $address->setAttribute('contact_id',
                            $this->contacts
                                ->first(static fn (Contact $contact): bool => $contact->address()->is($address))
                                ?->getKey()
                        );
                    })
                    ->values();

                return CompanyAddressResource::collection($collection);
            }),
            'contacts' => with($this->contacts, static function (Collection $contacts): AnonymousResourceCollection {
                $collection = $contacts
                    ->loadMissing(['user'])
                    ->sortBy('created_at')
                    ->values();

                return CompanyContactResource::collection($collection);
            }),

            'permissions' => [
                'view' => $request->user()->can('view', $this->resource),
                'update' => $request->user()->can('update', $this->resource),
                'delete' => $request->user()->can('delete', $this->resource),
            ],

            'creation_date' => isset($this->creation_date) ? format('date', $this->creation_date) : null,
            'created_at' => $this->{$this->getCreatedAtColumn()}?->format(config('date.format_time')),
            'updated_at' => $this->{$this->getUpdatedAtColumn()}?->format(config('date.format_time')),
            'activated_at' => $this->activated_at,
        ];
    }
}
