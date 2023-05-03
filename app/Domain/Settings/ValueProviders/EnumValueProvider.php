<?php

namespace App\Domain\Settings\ValueProviders;

use Illuminate\Support\Str;

class EnumValueProvider implements ValueProvider
{
    public function __construct(
        protected readonly string $enum,
        protected readonly array $except = [],
        protected readonly ?string $label = null,
    ) {
    }

    public function __invoke(): array
    {
        return collect($this->enum::cases())
            ->lazy()
            ->filter(function (\UnitEnum $case): bool {
                return !in_array($case, $this->except, true);
            })
            ->map(function (\UnitEnum $case): array {
                $value = $case instanceof \BackedEnum
                    ? $case->value
                    : $case->name;

                return [
                    'label' => $this->resolveLabel($case),
                    'value' => $value,
                ];
            })
            ->values()
            ->all();
    }

    private function resolveLabel(\UnitEnum $case): string
    {
        if (!$this->label) {
            return Str::headline($case->name);
        }

        $value = $case instanceof \BackedEnum
            ? $case->value
            : $case->name;

        $value = Str::snake((string) $value);

        $transKey = "{$this->label}_$value";

        $label = __($transKey);

        if ($transKey === $label) {
            return Str::headline($case->name);
        }

        return $label;
    }
}
