<?php

namespace App\Http\Requests\ImportableColumn;

use App\DTO\ImportableColumn\CreateColumnData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\QuoteFile\ImportableColumn;
use App\Rules\UniqueAliases;

class CreateImportableColumnRequest extends FormRequest
{
    protected ?CreateColumnData $createColumnData = null;

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
                Rule::unique('importable_columns', 'header')->whereNull('deleted_at')->where('is_temp', false)
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

    public function getCreateColumnData(): CreateColumnData
    {
        return $this->createColumnData ??= new CreateColumnData([
            'header' => $this->input('header'),
            'country_id' => $this->input('country_id'),
            'type' => $this->input('type'),
            'is_system' => false,
            'is_temp' => false,
            'aliases' => $this->input('aliases'),
        ]);
    }
}
