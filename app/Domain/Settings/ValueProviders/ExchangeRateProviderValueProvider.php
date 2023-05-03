<?php

namespace App\Domain\Settings\ValueProviders;

class ExchangeRateProviderValueProvider implements ValueProvider
{
    public function __invoke(): array
    {
        return [
            [
                'label' => 'HMRC',
                'value' => 'HMRC',
            ],
        ];
    }
}
