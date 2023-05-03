<?php

namespace App\Domain\Margin\Resources\V1;

use App\Domain\Country\Models\Country;
use App\Domain\Margin\Models\CountryMargin;
use App\Domain\Vendor\Models\Vendor;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CountryMargin
 */
final class CountryMarginResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->getKey(),
            'user_id' => $this->user()->getParentKey(),
            'value' => $this->value,
            'is_fixed' => (bool) $this->is_fixed,
            'vendor_id' => $this->vendor()->getParentKey(),
            'vendor' => \transform($this->vendor, static function (Vendor $vendor): array {
                return [
                    'id' => $vendor->getKey(),
                    'short_code' => $vendor->short_code,
                    'name' => $vendor->name,
                ];
            }),
            'country_id' => $this->country()->getParentKey(),
            'country' => \transform($this->country, static function (Country $country): array {
                return [
                    'id' => $country->getKey(),
                    'iso_3166_2' => $country->iso_3166_2,
                    'name' => $country->name,
                ];
            }),
            'quote_type' => $this->quote_type,
            'method' => $this->method,
            'created_at' => $this->{$this->getCreatedAtColumn()},
            'updated_at' => $this->{$this->getUpdatedAtColumn()},
            'activated_at' => $this->activated_at,
        ];
    }
}
