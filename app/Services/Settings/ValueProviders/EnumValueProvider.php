<?php

namespace App\Services\Settings\ValueProviders;

class EnumValueProvider implements ValueProvider
{
    public function __construct(
        protected readonly string $enum,
    ) {
    }

    public function __invoke(): array
    {
        return collect($this->enum::cases())
            ->map(static function (\BackedEnum|\UnitEnum $case): array {
                $value = $case instanceof \BackedEnum
                    ? $case->value
                    : $case->name;

                return [
                    'label' => $case->name,
                    'value' => $value,
                ];
            })
            ->all();
    }
}