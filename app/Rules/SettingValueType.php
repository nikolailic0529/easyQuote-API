<?php

namespace App\Rules;

use App\Models\System\SystemSetting;
use App\Services\Settings\SettingsDataProviderService;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class SettingValueType implements Rule, DataAwareRule
{
    protected array $data = [];

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $leadingAttributePath = static::resolveLeadingAttributePath($attribute);

        if (array_key_exists($leadingAttributePath, $this->data) === false) {
            return false;
        }

        $propertyId = Arr::get($this->data, "$leadingAttributePath.id");

        if (is_scalar($propertyId) === false) {
            return false;
        }

        /** @var SystemSetting $settingsProperty */
        $settingsProperty = SystemSetting::query()->find($propertyId);

        if (null === $settingsProperty) {
            return false;
        }

        $possibleValues = app(SettingsDataProviderService::class)
            ->resolvePossibleValuesForSettingsProperty($settingsProperty);

        // Ignore when possible values are set
        if ($possibleValues !== null) {
            return true;
        }

        $rule = $this->resolveValidationRulesForType($settingsProperty->type);

        $validator = Validator::make(['value' => $value], ['value' => $rule]);

        return $validator->passes();
    }

    protected static function resolveLeadingAttributePath(string $attribute): string
    {
        return implode('.', explode('.', $attribute, -1));
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The given value for settings property has invalid type.';
    }

    protected function resolveValidationRulesForType(string $type): array
    {
        return match ($type) {
            'float', 'integer' => ['min:0', 'numeric'],
            'boolean' => ['boolean'],
            default => [],
        };
    }

    public function setData($data): void
    {
        $this->data = $data;
    }
}
