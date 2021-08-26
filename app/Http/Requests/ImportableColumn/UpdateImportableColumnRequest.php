<?php

namespace App\Http\Requests\ImportableColumn;

use App\DTO\ImportableColumn\UpdateColumnData;
use App\Models\QuoteFile\ImportableColumn;
use App\Rules\UniqueAliases;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImportableColumnRequest extends FormRequest
{
    protected ?UpdateColumnData $updateColumnData = null;

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
                Rule::unique('importable_columns', 'header')->whereNull('deleted_at')->where('is_temp', false)->ignore($importableColumn),
            ],
            'country_id' => [
                'string',
                'uuid',
                Rule::exists('countries', 'id')->whereNull('deleted_at'),
            ],
            'type' => [
                'string',
                Rule::in(ImportableColumn::TYPES),
            ],
            'aliases' => [
                'array',
                (new UniqueAliases)->ignore($importableColumn),
            ],
            'aliases.*' => [
                'string',
                'min:2',
                'distinct',
            ],
        ];
    }

    public function getColumnModel(): ImportableColumn
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->route('importable_column');
    }

    public function getUpdateColumnData(): UpdateColumnData
    {
        return $this->updateColumnData ??= with($this->getColumnModel(), function (ImportableColumn $column) {
            $replaceData = [];

            if ($column->is_system) {
                $replaceData = [
                    'header' => $column->header,
                    'name' => $column->name,
                    'country_id' => $column->country_id,
                    'type' => $column->type,
                    'order' => $column->order,
                    'is_system' => (bool)$column->is_system,
                    'is_temp' => (bool)$column->is_temp,
                ];
            }

            return new UpdateColumnData(array_merge([
                'header' => $this->input('header'),
                'country_id' => $this->input('country_id'),
                'type' => $this->input('type'),
                'is_system' => false,
                'is_temp' => false,
                'aliases' => $this->input('aliases'),
            ], $replaceData));
        });
    }
}
