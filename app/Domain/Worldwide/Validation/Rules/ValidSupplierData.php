<?php

namespace App\Domain\Worldwide\Validation\Rules;

use App\Domain\CustomField\Models\CustomField;
use Illuminate\Contracts\Support\MessageBag as MessageBagContract;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

class ValidSupplierData implements Rule
{
    protected string $supplierNameAttribute = 'supplier_name';
    protected string $countryNameAttribute = 'country_name';
    protected bool $blankValuesAllowed = false;

    protected MessageBagContract $messageBag;

    public function __construct()
    {
        $this->messageBag = new MessageBag();
    }

    public static function new(): static
    {
        return new static();
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        if ($this->blankValuesAllowed && blank($value[$this->supplierNameAttribute] ?? null) && blank($value[$this->countryNameAttribute] ?? null)) {
            return true;
        }

        if (!isset($value[$this->supplierNameAttribute]) || !isset($value[$this->countryNameAttribute])) {
            return false;
        }

        $supN = (int) Str::afterLast($attribute, '.') + 1;

        $fieldName = "opportunity_distributor$supN";

        /** @var CustomField $field */
        $field = CustomField::query()
            ->where('field_name', $fieldName)
            ->first();

        if (null === $field) {
            return false;
        }

        /** @var \App\Domain\CustomField\Models\CustomFieldValue $fieldValue */
        $fieldValue = $field->values()->where('field_value', $value[$this->supplierNameAttribute])->first();

        if (null === $fieldValue) {
            $this->messageBag->add($this->supplierNameAttribute,
                sprintf('The given supplier [%s] is not valid.', $value[$this->supplierNameAttribute])
            );

            return false;
        }

        $supplierAllowedForCountry = $fieldValue->allowedBy()
            ->whereIn('field_value', $this->resolveCountryFieldAliases($value[$this->countryNameAttribute]))
            ->exists();

        if (!$supplierAllowedForCountry) {
            $this->messageBag->add($this->countryNameAttribute,
                sprintf(
                    'The supplier [%s] is not allowed for the country [%s]',
                    $fieldValue->field_value,
                    $value[$this->countryNameAttribute]
                ));

            return false;
        }

        return true;
    }

    protected function resolveCountryFieldAliases(string $country): array
    {
        return collect(config('pipeliner.custom_fields.country_field_aliases', []))
            ->filter(static function (string $name) use ($country): bool {
                return $country === $name;
            })
            ->values()
            ->keys()
            ->merge([$country])
            ->all();
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return $this->messageBag->isNotEmpty()
            ? sprintf('The given supplier data is invalid (%s).', implode(', ', $this->messageBag->all()))
            : 'The given supplier data is invalid.';
    }

    public function setCountryNameAttribute(string $key): ValidSupplierData
    {
        $this->countryNameAttribute = $key;

        return $this;
    }

    public function setSupplierNameAttribute(string $key): ValidSupplierData
    {
        $this->supplierNameAttribute = $key;

        return $this;
    }

    public function allowBlankValues(bool $value = true): ValidSupplierData
    {
        $this->blankValuesAllowed = $value;

        return $this;
    }
}
