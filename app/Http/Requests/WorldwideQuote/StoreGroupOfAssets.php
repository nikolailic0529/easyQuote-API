<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\AssetsGroupData;
use App\Models\Quote\WorldwideQuote;
use App\Models\WorldwideQuoteAsset;
use App\Models\WorldwideQuoteAssetsGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupOfAssets extends FormRequest
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
                    ->where('worldwide_quote_version_id', $this->getQuote()->active_version_id)
            ],
            'search_text' => [
                'bail', 'required', 'string', 'max:250'
            ],
            'assets' => [
                'bail', 'required', 'array'
            ],
            'assets.*' => [
                'bail', 'required', 'uuid',
                Rule::exists(WorldwideQuoteAsset::class, 'id')
                    ->where('worldwide_quote_id', $this->getQuote()->active_version_id)
            ]
        ];
    }

    public function getQuote(): WorldwideQuote
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->route('worldwide_quote');
    }

    public function getAssetsGroupData(): AssetsGroupData
    {
        return $this->assetsGroupData ??= new AssetsGroupData([
            'group_name' => $this->input('group_name'),
            'search_text' => $this->input('search_text'),
            'assets' => $this->input('assets')
        ]);
    }

    public function loadGroupAttributes(WorldwideQuoteAssetsGroup $group): WorldwideQuoteAssetsGroup
    {
        return tap($group, function (WorldwideQuoteAssetsGroup $group) {
            $group
                ->loadMissing('assets')
                ->loadCount('assets')
                ->loadSum('assets', 'price');
        });
    }
}
