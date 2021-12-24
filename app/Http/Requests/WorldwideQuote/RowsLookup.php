<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\DistributionRowsLookupData;
use App\Models\QuoteFile\DistributionRowsGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RowsLookup extends FormRequest
{
    protected ?DistributionRowsLookupData $rowsLookupData = null;

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
            'rows_group_id' => [
                'bail', 'nullable', 'uuid', Rule::exists(DistributionRowsGroup::class, 'id')
            ]
        ];
    }

    public function getRowsLookupData()
    {
        return $this->rowsLookupData ??= with(true, function () {
            $rowsGroup = transform($this->input('rows_group_id'), function ($rowsGroupId) {
                return DistributionRowsGroup::find($rowsGroupId);
            });

            $input = collect(explode(',', $this->input('input')))->map('trim')->filter()->all();

            return new DistributionRowsLookupData([
                'input' => $input,
                'rows_group' => $rowsGroup
            ]);
        });
    }
}
