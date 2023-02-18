<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteAssetsGroup;
use App\Domain\Worldwide\Resources\V1\Quote\AssetsGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveAssetsBetweenGroupsOfAssetsRequest extends FormRequest
{
    protected ?WorldwideQuoteAssetsGroup $outputAssetsGroup = null;

    protected ?WorldwideQuoteAssetsGroup $inputAssetsGroup = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'output_assets_group_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(WorldwideQuoteAssetsGroup::class, 'id')->where('worldwide_quote_version_id', $this->getQuote()->active_version_id),
            ],
            'assets' => [
                'bail', 'required', 'array',
            ],
            'assets.*' => [
                'bail', 'required', 'uuid', 'distinct',
                Rule::exists('worldwide_quote_assets_group_asset', 'asset_id')->where('group_id', $this->input('output_assets_group_id')),
            ],
            'input_assets_group_id' => [
                'bail', 'required', 'uuid', 'different:output_assets_group_id',
                Rule::exists(WorldwideQuoteAssetsGroup::class, 'id')->where('worldwide_quote_version_id', $this->getQuote()->active_version_id),
            ],
        ];
    }

    public function getQuote(): WorldwideQuote
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->route('worldwide_quote');
    }

    public function messages()
    {
        return [
            'input_assets_group_id.different' => 'Output & Input Groups must be different.',
            'assets.*.exists' => 'The selected assets don\'t exist in the Output Group.',
        ];
    }

    public function getMovedAssets(): array
    {
        return $this->input('assets');
    }

    public function getChangedRowsGroups(): array
    {
        $outputGroup = $this->loadGroupAttributes($this->getOutputAssetsGroup());
        $inputGroup = $this->loadGroupAttributes($this->getInputAssetsGroup());

        return [
            'output_assets_group' => AssetsGroup::make($outputGroup),
            'input_assets_group' => \App\Domain\Worldwide\Resources\V1\Quote\AssetsGroup::make($inputGroup),
        ];
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

    public function getOutputAssetsGroup(): WorldwideQuoteAssetsGroup
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->outputAssetsGroup ??= WorldwideQuoteAssetsGroup::query()->findOrFail($this->input('output_assets_group_id'));
    }

    public function getInputAssetsGroup(): WorldwideQuoteAssetsGroup
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->inputAssetsGroup ??= WorldwideQuoteAssetsGroup::query()->findOrFail($this->input('input_assets_group_id'));
    }
}
