<?php

namespace App\Services\Settings\ValueProviders;

use Symfony\Component\Intl\Currencies;

class CurrencyValueProvider implements ValueProvider
{
    public function __invoke(): array
    {
        return collect(Currencies::getCurrencyCodes())
            ->map(static function (string $code): array {
                $name = Currencies::getName($code);

                return [
                    'label' => "$code ($name)",
                    'value' => $code,
                ];
            })
            ->all();
    }
}