<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\WorldwideQuote\InitializeWorldwideQuoteAssetData;
use App\Models\Address;
use App\Models\Vendor;
use DateTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class InitializeQuoteAsset extends FormRequest
{
    protected ?InitializeWorldwideQuoteAssetData $assetData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'vendor_id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(Vendor::class, 'id')->whereNull('deleted_at')
            ],
            'assets.*.machine_address_id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(Address::class, 'id')->whereNull('deleted_at')
            ],
            'country' => [
                'bail', 'nullable', 'string', 'size:2'
            ],
            'serial_no' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'sku' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'service_sku' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'product_name' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'expiry_date' => [
                'bail', 'nullable', 'date',
            ],
            'service_level_description' => [
                'bail', 'nullable', 'string', 'max:500'
            ],
            'price' => [
                'bail', 'nullable', 'numeric', 'min:-999999', 'max:999999'
            ],
        ];
    }

    public function getInitializeAssetData(): InitializeWorldwideQuoteAssetData
    {
        return $this->assetData ??= new InitializeWorldwideQuoteAssetData([
            'vendor_id' => $this->input('vendor_id'),
            'machine_address_id' => $this->input('machine_address_id'),
            'country_code' => $this->input('country'),
            'serial_no' => $this->input('serial_no'),
            'sku' => $this->input('sku'),
            'service_sku' => $this->input('service_sku'),
            'product_name' => $this->input('product_name'),
            'expiry_date' => transform($this->input('expiry_date'), fn(string $date) => Carbon::createFromFormat(DateTime::RFC3339_EXTENDED, $date)),
            'service_level_description' => $this->input('service_level_description'),
            'price' => transform($this->input('price'), fn($value) => (float)$value)
        ]);
    }
}
