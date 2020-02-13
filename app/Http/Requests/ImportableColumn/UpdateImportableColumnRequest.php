<?php

namespace App\Http\Requests\ImportableColumn;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\QuoteFile\ImportableColumn;
use App\Rules\UniqueAliases;
use Arr;

class UpdateImportableColumnRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $importableColumn = $this->route('importable_column');

        return [
            'header' => [
                'string',
                'min:2',
                'max:100',
                Rule::unique('importable_columns', 'header')->whereNull('deleted_at')->where('is_temp', false)->ignore($importableColumn)
            ],
            'country_id' => [
                'string',
                'uuid',
                Rule::exists('countries', 'id')->whereNull('deleted_at')
            ],
            'type'  => [
                'string',
                Rule::in(ImportableColumn::TYPES)
            ],
            'aliases' => [
                'array',
                (new UniqueAliases)->ignore($importableColumn)
            ],
            'aliases.*' => [
                'string',
                'min:2',
                'distinct'
            ]
        ];
    }

    protected function prepareForValidation()
    {
        $importableColumn = $this->route('importable_column');

        if ($importableColumn->isNotSystem()) {
            return;
        }

        $source = $this->getInputSource();

        $source->replace(Arr::only($source->all(), 'aliases'));
    }
}
