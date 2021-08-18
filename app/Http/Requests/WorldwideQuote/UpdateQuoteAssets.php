<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\QuoteStages\PackAssetsCreationStage;
use App\DTO\WorldwideQuote\WorldwideQuoteAssetData;
use App\DTO\WorldwideQuote\WorldwideQuoteAssetDataCollection;
use App\Enum\PackQuoteStage;
use App\Models\Address;
use App\Models\Data\Currency;
use App\Models\Quote\WorldwideQuote;
use App\Models\Vendor;
use App\Models\WorldwideQuoteAsset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class UpdateQuoteAssets extends FormRequest
{
    protected ?PackAssetsCreationStage $quoteStage = null;

    protected ?WorldwideQuoteAssetDataCollection $assetDataCollection;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'assets' => [
                'bail', 'required', 'array'
            ],
            'assets.*.id' => [
                'bail', 'uuid',
                Rule::exists(WorldwideQuoteAsset::class, 'id')
                    ->where('worldwide_quote_id', $this->getQuote()->active_version_id)
            ],
            'assets.*.buy_currency_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Currency::class, 'id')
            ],
            'assets.*.vendor_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Vendor::class, 'id')->whereNull('deleted_at')
            ],
            'assets.*.machine_address_id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(Address::class, 'id')->whereNull('deleted_at')
            ],
            'assets.*.country' => [
                'bail', 'nullable', 'string', 'size:2'
            ],
            'assets.*.serial_no' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'assets.*.sku' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'assets.*.service_sku' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'assets.*.product_name' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'assets.*.expiry_date' => [
                'bail', 'nullable', 'date_format:Y-m-d',
            ],
            'assets.*.service_level_description' => [
                'bail', 'nullable', 'string', 'max:500'
            ],
            'assets.*.buy_price' => [
                'bail', 'nullable', 'numeric', 'min:-999999', 'max:999999'
            ],
            'assets.*.buy_price_margin' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:100'
            ],
            'assets.*.price' => [
                'bail', 'nullable', 'numeric', 'min:-999999', 'max:999999'
            ],
            'assets.*.original_price' => [
                'bail', 'nullable', 'numeric', 'min:-999999', 'max:999999'
            ],
            'assets.*.exchange_rate_margin' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:999999',
            ],
            'assets.*.exchange_rate_value' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:999999',
            ],
            'stage' => [
                'bail', 'required', Rule::in(PackQuoteStage::getLabels())
            ],
        ];
    }

    public function getQuote(): WorldwideQuote
    {
        /** @var WorldwideQuote $model */
        $model = $this->route('worldwide_quote');

        return $model;
    }

    public function messages()
    {
        return [
            'assets.required' => "No assets provided.",
            'assets.*.buy_currency_id.required' => "One or more assets don't have a buy currency.",
        ];
    }

    public function getAssetDataCollection(): WorldwideQuoteAssetDataCollection
    {
        return $this->assetDataCollection ??= with($this->input('assets'), function (array $assets) {

            $collection = array_map(function (array $asset) {
                return new WorldwideQuoteAssetData([
                    'id' => $asset['id'],
                    'buy_currency_id' => $asset['buy_currency_id'],
                    'vendor_id' => $asset['vendor_id'],
                    'machine_address_id' => $asset['machine_address_id'] ?? null,
                    'country_code' => $asset['country'] ?? null,
                    'serial_no' => $asset['serial_no'] ?? null,
                    'sku' => $asset['sku'] ?? null,
                    'service_sku' => $asset['service_sku'] ?? null,
                    'product_name' => $asset['product_name'] ?? null,
                    'expiry_date' => transform($asset['expiry_date'] ?? null, fn(string $date) => Carbon::createFromFormat('Y-m-d', $date)),
                    'service_level_description' => $asset['service_level_description'] ?? null,
                    'buy_price' => transform($asset['buy_price'] ?? null, fn (string $price) => (float)$price),
                    'buy_price_margin' => transform($asset['buy_price_margin'] ?? null, fn (string $price) => (float)$price),
                    'price' => transform($asset['price'] ?? null, fn(string $price) => (float)$price),
                    'original_price' => transform($asset['original_price'] ?? null, fn(string $price) => (float)$price),
                    'exchange_rate_value' => transform($asset['exchange_rate_value'] ?? null, fn(string $value) => (float)$value),
                    'exchange_rate_margin' => transform($asset['exchange_rate_margin'] ?? null, fn(string $value) => (float)$value),
                ]);
            }, $assets);

            return new WorldwideQuoteAssetDataCollection($collection);

        });
    }

    public function getStage(): PackAssetsCreationStage
    {
        return $this->quoteStage ??= new PackAssetsCreationStage([
            'stage' => PackQuoteStage::getValueOfLabel($this->input('stage'))
        ]);
    }
}
