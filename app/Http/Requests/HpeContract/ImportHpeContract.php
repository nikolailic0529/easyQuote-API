<?php

namespace App\Http\Requests\HpeContract;

use App\DTO\HpeContract\HpeContractImportData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportHpeContract extends FormRequest
{
    protected ?HpeContractImportData $importData;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'date_format' => [
                'bail', 'nullable', 'string',
                Rule::in(['d/m/Y', 'm/d/Y', 'd/m/y', 'm/d/y', 'd.m.Y', 'd.m.y', 'm.d.Y', 'm.d.y'])
            ]
        ];
    }

    public function getImportData(): HpeContractImportData
    {
        return $this->importData ??= new HpeContractImportData([
            'date_format' => $this->input('date_format')
        ]);
    }
}
