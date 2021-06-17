<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\WorldwideQuote\AssetsLookupData;
use App\Models\WorldwideQuoteAssetsGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetsLookup extends FormRequest
{
    protected ?AssetsLookupData $assetsLookupData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'input' => [
                'bail', 'required', 'string', 'max:500'
            ],
            'assets_group_id' => [
                'bail', 'nullable', 'uuid', Rule::exists(WorldwideQuoteAssetsGroup::class, 'id')
            ]
        ];
    }

    public function getAssetsLookupData(): AssetsLookupData
    {
        return $this->assetsLookupData ??= value(function () {
            $rowsGroup = transform($this->input('assets_group_id'), function (string $assetsGroupId) {
                return WorldwideQuoteAssetsGroup::query()->find($assetsGroupId);
            });

            $input = array_values(
                array_filter(
                    array_map('trim', explode(',', $this->input('input')))
                )
            );

            return new AssetsLookupData([
                'input' => $input,
                'assets_group' => $rowsGroup
            ]);
        });
    }
}
