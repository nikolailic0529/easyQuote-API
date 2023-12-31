<?php

namespace App\Domain\Asset\Requests;

use App\Domain\Address\Models\Address;
use App\Domain\Asset\DataTransferObjects\UpdateAssetData;
use App\Domain\Asset\Models\Asset;
use App\Domain\Asset\Models\AssetCategory;
use App\Domain\Vendor\Models\{Vendor};
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    protected ?\App\Domain\Asset\DataTransferObjects\UpdateAssetData $updateAssetData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'asset_category_id' => ['bail', 'required', 'uuid', Rule::exists(AssetCategory::class, 'id')->whereNull('deleted_at')],
            'address_id' => ['bail', 'nullable', 'uuid', Rule::exists(Address::class, 'id')->whereNull('deleted_at')],
            'vendor_id' => ['bail', 'required', 'uuid', Rule::exists(Vendor::class, 'id')->whereNull('deleted_at')],
            'unit_price' => ['nullable', 'numeric'],
            'base_warranty_start_date' => ['required', 'date_format:Y-m-d'],
            'base_warranty_end_date' => ['required', 'date_format:Y-m-d'],
            'active_warranty_start_date' => ['required', 'date_format:Y-m-d'],
            'active_warranty_end_date' => ['required', 'date_format:Y-m-d'],
            'item_number' => ['nullable', 'string', 'max:191'],
            'product_number' => ['required', 'string', 'max:191'],
            'serial_number' => [
                'required',
                'string',
                'max:191',
                Rule::unique(Asset::class)
                    ->ignore($this->getAssetModel())
                    ->where('user_id', $this->getAssetModel()->user_id)
                    ->where('vendor_id', $this->input('vendor_id'))
                    ->where('product_number', $this->input('product_number'))
                    ->whereNull('deleted_at'),
            ],
            'product_description' => ['nullable', 'string', 'max:191'],
            'product_image' => ['nullable', 'string', 'max:191'],
        ];
    }

    public function messages()
    {
        return [
            'serial_number.unique' => 'The asset with the same serial number & product number already exists.',
        ];
    }

    public function getAssetModel(): Asset
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->route('asset');
    }

    public function getUpdateAssetData(): UpdateAssetData
    {
        return $this->updateAssetData ??= value(function () {
            return new UpdateAssetData([
                'asset_category_id' => $this->input('asset_category_id'),
                'address_id' => $this->input('address_id'),
                'vendor_id' => $this->input('vendor_id'),
                'vendor_short_code' => with($this->input('vendor_id'), function (string $vendorKey): ?string {
                    return Vendor::query()->whereKey($vendorKey)->value('short_code');
                }),
                'unit_price' => (float) $this->input('unit_price'),
                'base_warranty_start_date' => $this->input('base_warranty_start_date'),
                'base_warranty_end_date' => $this->input('base_warranty_end_date'),
                'active_warranty_start_date' => $this->input('active_warranty_start_date'),
                'active_warranty_end_date' => $this->input('active_warranty_end_date'),
                'item_number' => $this->input('item_number'),
                'product_number' => $this->input('product_number'),
                'serial_number' => $this->input('serial_number'),
                'product_description' => $this->input('product_description'),
                'product_image' => $this->input('product_image'),
            ]);
        });
    }
}
