<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\WorldwideQuote\InitializeWorldwideQuoteAssetCollection;
use App\DTO\WorldwideQuote\InitializeWorldwideQuoteAssetData;
use App\Models\Address;
use App\Models\Data\Currency;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class BatchInitializeQuoteAsset extends FormRequest
{
    protected ?InitializeWorldwideQuoteAssetCollection $assetCollection = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'assets' => ['required', 'array'],
            'assets.*.vendor_id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(Vendor::class, 'id')->whereNull('deleted_at'),
            ],
            'assets.*.machine_address_id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(Address::class, 'id')->whereNull('deleted_at'),
            ],
            'assets.*.buy_currency_id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(Currency::class, 'id'),
            ],
            'assets.*.country' => [
                'bail', 'nullable', 'string', 'size:2',
            ],
            'assets.*.serial_no' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'assets.*.sku' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'assets.*.service_sku' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'assets.*.product_name' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'assets.*.expiry_date' => [
                'bail', 'nullable', 'date',
            ],
            'assets.*.service_level_description' => [
                'bail', 'nullable', 'string', 'max:500',
            ],
            'assets.*.buy_price' => [
                'bail', 'nullable', 'numeric', 'min:-999999', 'max:999999',
            ],
            'assets.*.buy_price_margin' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:999999',
            ],
            'assets.*.price' => [
                'bail', 'nullable', 'numeric', 'min:-999999', 'max:999999',
            ],
            'assets.*.original_price' => [
                'bail', 'nullable', 'numeric', 'min:-999999', 'max:999999',
            ],
            'assets.*.exchange_rate_margin' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:999999',
            ],
            'assets.*.exchange_rate_value' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:999999',
            ],
            'assets.*.is_warranty_checked' => [
                'bail', 'nullable', 'boolean',
            ],
        ];
    }

    public function getInitializeAssetCollection(): InitializeWorldwideQuoteAssetCollection
    {
        return $this->assetCollection ??= value(function (): InitializeWorldwideQuoteAssetCollection {
            $collection = $this->collect('assets')
                ->map(static function (array $asset): InitializeWorldwideQuoteAssetData {
                    return new InitializeWorldwideQuoteAssetData([
                        'vendor_id' => Arr::get($asset, 'vendor_id'),
                        'buy_currency_id' => Arr::get($asset, 'buy_currency_id'),
                        'machine_address_id' => Arr::get($asset, 'machine_address_id'),
                        'country_code' => Arr::get($asset, 'country'),
                        'serial_no' => Arr::get($asset, 'serial_no'),
                        'sku' => Arr::get($asset, 'sku'),
                        'service_sku' => Arr::get($asset, 'service_sku'),
                        'product_name' => Arr::get($asset, 'product_name'),
                        'expiry_date' => transform(Arr::get($asset, 'expiry_date'), fn(string $date) => Carbon::createFromFormat('Y-m-d\TH:i:s.vP', $date)),
                        'service_level_description' => Arr::get($asset, 'service_level_description'),
                        'buy_price' => transform(Arr::get($asset, 'buy_price'), fn($value) => (float)$value),
                        'buy_price_margin' => transform(Arr::get($asset, 'buy_price_margin'), fn($value) => (float)$value),
                        'price' => transform(Arr::get($asset, 'price'), fn($value) => (float)$value),
                        'original_price' => transform(Arr::get($asset, 'original_price'), fn($value) => (float)$value),
                        'exchange_rate_margin' => transform(Arr::get($asset, 'exchange_rate_margin'), fn($value) => (float)$value),
                        'exchange_rate_value' => transform(Arr::get($asset, 'exchange_rate_value'), fn($value) => (float)$value),
                        'is_warranty_checked' => filter_var(Arr::get($asset, 'is_warranty_checked'), FILTER_VALIDATE_BOOLEAN),
                    ]);
                });

            return new InitializeWorldwideQuoteAssetCollection($collection->all());
        });
    }
}
