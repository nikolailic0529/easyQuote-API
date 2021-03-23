<?php

namespace App\Http\Requests\MappedRow;

use App\DTO\MappedRow\MappedRowFieldData;
use App\DTO\MappedRow\UpdateMappedRowFieldCollection;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDistributionMappedRow extends FormRequest
{
    protected ?UpdateMappedRowFieldCollection $rowFieldCollection = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'product_no' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'service_sku' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'description' => [
                'bail', 'nullable', 'string', 'max:250'
            ],
            'serial_no' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'date_from' => [
                'bail', 'nullable', 'date_format:Y-m-d'
            ],
            'date_to' => [
                'bail', 'nullable', 'date_format:Y-m-d'
            ],
            'qty' => [
                'bail', 'nullable', 'integer', 'min:0', 'max:999999'
            ],
            'price' => [
                'bail', 'nullable', 'numeric', 'min:-999999', 'max:999999'
            ],
            'pricing_document' => [
                'bail', 'nullable', 'string', 'max:250'
            ],
            'system_handle' => [
                'bail', 'nullable', 'string', 'max:250'
            ],
            'searchable' => [
                'bail', 'nullable', 'string', 'max:250'
            ],
            'service_level_description' => [
                'bail', 'nullable', 'string', 'max:250'
            ],
        ];
    }

    public function getUpdateMappedRowFieldCollection(): UpdateMappedRowFieldCollection
    {
        return $this->rowFieldCollection ??= with(true, function () {
            $validatedData = $this->validated();

            $collection = array_map(function ($value, string $key) {
                return new MappedRowFieldData([
                    'field_name' => $key,
                    'field_value' => $value
                ]);
            }, $validatedData, array_keys($validatedData));

            return new UpdateMappedRowFieldCollection($collection);
        });
    }
}
