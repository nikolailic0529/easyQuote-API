<?php

namespace App\Services\Settings\ValueProviders;

use Illuminate\Support\Str;
use UnitEnum;

class EnumValueProvider implements ValueProvider
{
    public function __construct(
        protected readonly string $enum,
        protected readonly array $except = [],
    ) {
    }

    public function __invoke(): array
    {
        return collect($this->enum::cases())
            ->lazy()
            ->filter(function (UnitEnum $case): bool {
                return !in_array($case, $this->except, true);
            })
            ->map(static function (UnitEnum $case): array {
                $value = $case instanceof \BackedEnum
                    ? $case->value
                    : $case->name;

                return [
                    'label' => Str::headline($case->name),
                    'value' => $value,
                ];
            })
            ->values()
            ->all();
    }
}