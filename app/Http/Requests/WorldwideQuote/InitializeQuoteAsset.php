<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\WorldwideQuote\InitializeWorldwideQuoteAssetData;
use App\Models\Address;
use App\Models\Data\Currency;
use App\Models\Vendor;
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
                Rule::exists(Vendor::class, 'id')->whereNull('deleted_at'),
            ],
            'machine_address_id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(Address::class, 'id')->whereNull('deleted_at'),
            ],
            'buy_currency_id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(Currency::class, 'id'),
            ],
            'country' => [
                'bail', 'nullable', 'string', 'size:2',
            ],
            'serial_no' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'sku' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'service_sku' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'product_name' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'expiry_date' => [
                'bail', 'nullable', 'date',
            ],
            'service_level_description' => [
                'bail', 'nullable', 'string', 'max:500',
            ],
            'buy_price' => [
                'bail', 'nullable', 'numeric', 'min:-999999', 'max:999999',
            ],
            'buy_price_margin' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:999999',
            ],
            'price' => [
                'bail', 'nullable', 'numeric', 'min:-999999', 'max:999999',
            ],
            'original_price' => [
                'bail', 'nullable', 'numeric', 'min:-999999', 'max:999999',
            ],
            'exchange_rate_margin' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:999999',
            ],
            'exchange_rate_value' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:999999',
            ],
        ];
    }

    public function getInitializeAssetData(): InitializeWorldwideQuoteAssetData
    {
        return $this->assetData ??= new InitializeWorldwideQuoteAssetData([
            'vendor_id' => $this->input('vendor_id'),
            'buy_currency_id' => $this->input('buy_currency_id'),
            'machine_address_id' => $this->input('machine_address_id'),
            'country_code' => $this->input('country'),
            'serial_no' => $this->input('serial_no'),
            'sku' => $this->input('sku'),
            'service_sku' => $this->input('service_sku'),
            'product_name' => $this->input('product_name'),
            'expiry_date' => transform($this->input('expiry_date'), fn(string $date) => Carbon::createFromFormat('Y-m-d\TH:i:s.vP', $date)),
            'service_level_description' => $this->input('service_level_description'),
            'buy_price' => transform($this->input('buy_price'), fn($value) => (float)$value),
            'buy_price_margin' => transform($this->input('buy_price_margin'), fn($value) => (float)$value),
            'price' => transform($this->input('price'), fn($value) => (float)$value),
            'original_price' => transform($this->input('original_price'), fn($value) => (float)$value),
            'exchange_rate_margin' => transform($this->input('exchange_rate_margin'), fn($value) => (float)$value),
            'exchange_rate_value' => transform($this->input('exchange_rate_value'), fn($value) => (float)$value),
        ]);
    }
}
