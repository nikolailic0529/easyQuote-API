<?php

namespace App\Http\Requests\Asset;

use App\Contracts\Repositories\VendorRepositoryInterface as Vendors;
use App\Models\{
    Address,
    AssetCategory,
    Vendor
};
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAsset extends FormRequest
{
    protected Vendors $vendors;

    public function __construct(Vendors $vendors)
    {
        $this->vendors = $vendors;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'asset_category_id'             => ['bail', 'required', 'uuid', Rule::exists(AssetCategory::class, 'id')->whereNull('deleted_at')],
            'address_id'                    => ['bail', 'nullable', 'uuid', Rule::exists(Address::class, 'id')->whereNull('deleted_at')],
            'vendor_id'                     => ['bail', 'required', 'uuid', Rule::exists(Vendor::class, 'id')->whereNull('deleted_at')],
            'unit_price'                    => ['nullable', 'numeric'],
            'base_warranty_start_date'      => ['required', 'date_format:Y-m-d'],
            'base_warranty_end_date'        => ['required', 'date_format:Y-m-d'],
            'active_warranty_start_date'    => ['required', 'date_format:Y-m-d'],
            'active_warranty_end_date'      => ['required', 'date_format:Y-m-d'],
            'item_number'                   => ['nullable', 'string', 'max:191'],
            'product_number'                => ['required', 'string', 'max:191'],
            'serial_number'                 => ['required', 'string', 'max:191'],
            'product_description'           => ['nullable', 'string', 'max:191'],
        ];
    }

    public function validated()
    {
        $vendor = $this->vendors->findCached($this->vendor_id);

        return parent::validated() + ['vendor_short_code' => optional($vendor)->short_code];
    }
}
