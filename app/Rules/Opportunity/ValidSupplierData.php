<?php

namespace App\Rules\Opportunity;

use App\Models\System\CustomField;
use App\Models\System\CustomFieldValue;
use Illuminate\Contracts\Support\MessageBag as MessageBagContract;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

class ValidSupplierData implements Rule
{
    protected string $supplierFieldKey = 'supplier_name';
    protected string $countryFieldKey = 'country_name';

    protected MessageBagContract $messageBag;

    public function __construct()
    {
        $this->messageBag = new MessageBag();
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (!is_array($value) || !isset($value[$this->supplierFieldKey]) || !isset($value[$this->countryFieldKey])) {
            return false;
        }

        $supN = (int)Str::afterLast($attribute, '.') + 1;

        $fieldName = "opportunity_distributor$supN";

        /** @var CustomField $field */
        $field = CustomField::query()
            ->where('field_name', $fieldName)
            ->first();

        if (null === $field) {
            return false;
        }

        /** @var CustomFieldValue $fieldValue */
        $fieldValue = $field->values()->where('field_value', $value[$this->supplierFieldKey])->first();

        if (null === $fieldValue) {
            $this->messageBag->add($this->supplierFieldKey, "The supplier doesn't exist in the field `$fieldName`");
            return false;
        }

        if ($fieldValue->allowedBy()->whereIn('field_value', $this->resolveCountryFieldAliases($value[$this->countryFieldKey]))->doesntExist()) {
            $this->messageBag->add($this->countryFieldKey, sprintf("The supplier is not allowed for the country `%s`", $value[$this->countryFieldKey]));
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
     *
     * @return string
     */
    public function message(): string
    {
        return $this->messageBag->isNotEmpty()
            ? sprintf('The given supplier data is invalid (%s).', implode(', ', $this->messageBag->all()))
            : 'The given supplier data is invalid.';
    }
}
