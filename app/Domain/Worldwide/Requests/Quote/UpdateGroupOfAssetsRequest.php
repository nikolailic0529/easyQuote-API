<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Asset\DataTransferObjects\AssetsGroupData;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteAsset;
use App\Domain\Worldwide\Models\WorldwideQuoteAssetsGroup;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteDataMapper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGroupOfAssetsRequest extends FormRequest
{
    protected ?AssetsGroupData $assetsGroupData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'group_name' => [
                'bail', 'required', 'string', 'max:250',
                Rule::unique(WorldwideQuoteAssetsGroup::class, 'group_name')
                    ->ignore($this->getAssetsGroup())
                    ->where('worldwide_quote_version_id', $this->getQuote()->active_version_id),
            ],
            'search_text' => [
                'bail', 'required', 'string', 'max:250',
            ],
            'assets' => [
                'bail', 'required', 'array',
            ],
            'assets.*' => [
                'bail', 'required', 'uuid',
                Rule::exists(WorldwideQuoteAsset::class, 'id')
                    ->where('worldwide_quote_id', $this->getQuote()->active_version_id),
            ],
        ];
    }

    public function getQuote(): WorldwideQuote
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->route('worldwide_quote');
    }

    public function getAssetsGroup(): WorldwideQuoteAssetsGroup
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->route('assets_group');
    }

    public function getAssetsGroupData(): AssetsGroupData
    {
        return $this->assetsGroupData ??= new AssetsGroupData([
            'group_name' => $this->input('group_name'),
            'search_text' => $this->input('search_text'),
            'assets' => $this->input('assets'),
        ]);
    }

    public function loadGroupAttributes(WorldwideQuoteAssetsGroup $group): WorldwideQuoteAssetsGroup
    {
        return tap($group, function (WorldwideQuoteAssetsGroup $group) {
            $group
                ->loadMissing('assets')
                ->loadCount('assets')
                ->loadSum('assets', 'price');

            /** @var WorldwideQuoteDataMapper $dataMapper */
            $dataMapper = $this->container[WorldwideQuoteDataMapper::class];

            $dataMapper->markExclusivityOfWorldwidePackQuoteAssetsForCustomer(quote: $group->worldwideQuoteVersion->worldwideQuote, assets: $group->assets);
        });
    }
}
