<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Worldwide\DataTransferObjects\Quote\AssetServiceLookupData;
use App\Domain\Worldwide\DataTransferObjects\Quote\AssetServiceLookupDataCollection;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Foundation\Http\FormRequest;

class BatchWarrantyLookupRequest extends FormRequest
{
    protected ?AssetServiceLookupDataCollection $lookupDataCollection = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'assets' => [
                'bail', 'required', 'array',
            ],
            'assets.*.id' => [
                'bail', 'required', 'uuid',
            ],
            'assets.*.index' => [
                'bail', 'required', 'integer', 'distinct',
            ],
            'assets.*.vendor_short_code' => [
                'bail', 'required', 'string', 'in:HPE,LEN',
            ],
            'assets.*.serial_no' => [
                'bail', 'required', 'string',
            ],
            'assets.*.sku' => [
                'bail', 'required', 'string',
            ],
            'assets.*.country' => [
                'bail', 'required', 'string', 'size:2',
            ],
            'assets.*.buy_currency_code' => [
                'bail', 'required', 'string', 'size:3',
            ],
        ];
    }

    public function getLookupDataCollection(WorldwideQuote $quote): AssetServiceLookupDataCollection
    {
        return $this->lookupDataCollection ??= with($this->input('assets'), function (array $assets) {
            $collection = array_map(fn (array $asset) => new AssetServiceLookupData([
                'asset_id' => $asset['id'],
                'asset_index' => (int) $asset['index'],
                'vendor_short_code' => $asset['vendor_short_code'],
                'serial_no' => $asset['serial_no'],
                'sku' => $asset['sku'],
                'country_code' => $asset['country'],
                'currency_code' => $asset['buy_currency_code'],
            ]), $assets);

            return new AssetServiceLookupDataCollection($collection);
        });
    }
}
