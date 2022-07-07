<?php

namespace App\Http\Requests\System;

use App\DTO\CustomField\UpdateCustomFieldValueCollection;
use App\DTO\CustomField\UpdateCustomFieldValueData;
use App\Models\System\CustomField;
use App\Models\System\CustomFieldValue;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UpdateCustomFieldValues extends FormRequest
{
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
                'bail', 'required', 'array',
                static function (string $attribute, array $values, \Closure $fail) {
                    $isDefaultSet = false;

                    foreach ($values as $value) {
                        if (($value['is_default'] ?? false) && $isDefaultSet) {
                            $fail('Only one value can be set as default.');
                        }

                        $isDefaultSet = $isDefaultSet || ($value['is_default'] ?? false);
                    }
                },
            ],
            'field_values.*.id' => [
                'bail', 'present', 'nullable', 'uuid',
                Rule::exists(CustomFieldValue::class, 'id')
                    ->whereNull('deleted_at')
                    ->where('custom_field_id', $this->getCustomFieldModel()->getKey()),
            ],
            'field_values.*.field_value' => [
                'bail', 'present', 'string', 'max:100',
            ],
            'field_values.*.is_default' => [
                'bail', 'nullable', 'boolean',
            ],
            'field_values.*.allowed_by' => [
                'bail', 'nullable', 'array',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (0 === count(Arr::wrap($value))) {
                        return;
                    }

                    $model = $this->getCustomFieldModel();

                    if (null === $model->parentField) {
                        $fail("The field `$model->field_name` is not dependent on any parent field.");
                    }
                },
            ],
            'field_values.*.allowed_by.*' => [
                'bail', 'uuid',
                Rule::exists(CustomFieldValue::class, (new CustomFieldValue())->getKeyName())
                    ->withoutTrashed()
                    ->where(function (BaseBuilder $builder): void {
                        $builder->where((new CustomFieldValue())->customField()->getForeignKeyName(), $this->getCustomFieldModel()->parentField()->getParentKey());
                    }),
            ],
        ];
    }

    public function getUpdateCustomFieldValueCollection(): UpdateCustomFieldValueCollection
    {
        return $this->customFieldValueCollection ??= with(true, function () {
            $collection = array_map(static function (array $fieldValue): UpdateCustomFieldValueData {
                return new UpdateCustomFieldValueData([
                    'entity_id' => $fieldValue['id'],
                    'field_value' => (string)$fieldValue['field_value'],
                    'is_default' => filter_var($fieldValue['is_default'] ?? false, FILTER_VALIDATE_BOOL),
                    'allowed_by' => $fieldValue['allowed_by'] ?? null,
                ]);
            }, $this->input('field_values'));

            return new UpdateCustomFieldValueCollection($collection);
        });
    }

    public function getCustomFieldModel(): CustomField
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->route('custom_field');
    }
}
