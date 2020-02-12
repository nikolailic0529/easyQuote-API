<?php

namespace App\Http\Requests\ImportableColumn;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\QuoteFile\ImportableColumn;
use App\Rules\UniqueAliases;

class CreateImportableColumnRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'header' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('importable_columns', 'header')->whereNull('deleted_at')
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
                'required',
                'array',
                new UniqueAliases
            ],
            'aliases.*' => [
                'required',
                'string',
                'min:2',
                'distinct'
            ]
        ];
    }
}
