<?php

namespace App\Http\Requests\ImportableColumn;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\QuoteFile\ImportableColumn;
use App\Rules\UniqueAliases;

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
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('importable_columns', 'header')->whereNull('deleted_at')->ignore($importableColumn)
            ],
            'country_id' => [
                'required',
                'string',
                'uuid',
                Rule::exists('countries', 'id')->whereNull('deleted_at')
            ],
            'type'  => [
                'required',
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
}
