<?php

namespace App\Http\Requests\System;

use App\DTO\CustomField\UpdateCustomFieldValueCollection;
use App\DTO\CustomField\UpdateCustomFieldValueData;
use App\Models\System\CustomField;
use App\Models\System\CustomFieldValue;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomFieldValues extends FormRequest
{
    protected ?CustomField $customFieldModel = null;

    protected ?UpdateCustomFieldValueCollection $customFieldValueCollection = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'field_values' => [
                'bail', 'required', 'array'
            ],
            'field_values.*.id' => [
                'bail', 'present', 'nullable', 'uuid',
                Rule::exists(CustomFieldValue::class, 'id')
                    ->whereNull('deleted_at')
                    ->where('custom_field_id', $this->getCustomFieldModel()->getKey())
            ],
            'field_value.*.field_value' => [
                'bail', 'present',
                function (string $attribute, $value, \Closure $fail) {
                    if (!is_scalar($value)) {
                        $fail('Field value must be a scalar.');
                    }
                }
            ],
            'field_value.*.is_default' => [
                'bail', 'nullable', 'boolean'
            ]
        ];
    }

    public function getUpdateCustomFieldValueCollection(): UpdateCustomFieldValueCollection
    {
        return $this->customFieldValueCollection ??= with(true, function () {
            $collection = array_map(fn(array $fieldValue) => new UpdateCustomFieldValueData([
                'entity_id' => $fieldValue['id'],
                'field_value' => (string)$fieldValue['field_value'],
                'is_default' => filter_var($fieldValue['is_default'] ?? false, FILTER_VALIDATE_BOOL),
            ]), $this->input('field_values'));

            return new UpdateCustomFieldValueCollection($collection);
        });
    }

    public function getCustomFieldModel(): CustomField
    {
        return $this->customFieldModel ??= with($this->route('custom_field_name'), function (string $customFieldName): CustomField {
            /** @var CustomField $customField */
            $customField = CustomField::query()
                ->where('field_name', $customFieldName)
                ->firstOrFail();

            return $customField;
        });
    }
}
