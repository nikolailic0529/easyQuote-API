<?php

namespace App\Domain\Settings\Validation\Rules;

use App\Domain\Settings\Models\SystemSetting;
use App\Domain\Settings\Services\SettingsDataProviderService;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;

class SettingValue implements Rule, DataAwareRule
{
    protected array $data = [];

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
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

        if (null === $settingsProperty || $settingsProperty->is_read_only) {
            return false;
        }

        $possibleValues = app(SettingsDataProviderService::class)
            ->resolvePossibleValuesForSettingsProperty($settingsProperty);

        if (null === $possibleValues) {
            return true;
        }

        $flattenPossibleValues = Arr::pluck($possibleValues, 'value');

        if (is_array($value)) {
            return blank(array_diff($value, $flattenPossibleValues));
        }

        return collect($flattenPossibleValues)
            ->lazy()
            ->map(static fn (mixed $v): string => (string) $v)
            ->containsStrict((string) $value);
    }

    protected static function resolveLeadingAttributePath(string $attribute): string
    {
        return implode('.', explode('.', $attribute, -1));
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The given value of settings property is invalid.';
    }

    public function setData($data): void
    {
        $this->data = $data;
    }
}
