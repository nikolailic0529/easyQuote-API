<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Worldwide\Models\WorldwideQuoteAssetsGroup;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteDataMapper;
use Illuminate\Foundation\Http\FormRequest;

class ShowStateOfAssetsGroupRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
        ];
    }

    public function loadGroupAttributes(WorldwideQuoteAssetsGroup $group): WorldwideQuoteAssetsGroup
    {
        return tap($group, function (WorldwideQuoteAssetsGroup $group) {
            $group
                ->load('assets', 'assets.vendor:id,short_code', 'assets.buyCurrency:id,code', 'assets.machineAddress')
                ->loadCount('assets')
                ->loadSum('assets', 'price');

            foreach ($group->assets as $asset) {
                $asset->setAttribute('vendor_short_code', $asset->vendor?->short_code);
                $asset->setAttribute('buy_currency_code', $asset->buyCurrency?->code);
                $asset->setAttribute('machine_address_string', WorldwideQuoteDataMapper::formatAddressToString($asset->machineAddress));
            }

            /** @var WorldwideQuoteDataMapper $dataMapper */
            $dataMapper = $this->container[WorldwideQuoteDataMapper::class];

            $dataMapper->markExclusivityOfWorldwidePackQuoteAssetsForCustomer(quote: $group->worldwideQuoteVersion->worldwideQuote, assets: $group->assets);
        });
    }
}
