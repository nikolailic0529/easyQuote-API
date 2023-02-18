<?php

use App\Domain\Settings\Enum\PriceCalculationDurationEnum;
use App\Domain\Settings\ValueProviders\CurrencyValueProvider;
use App\Domain\Settings\ValueProviders\EnumValueProvider;
use App\Domain\Settings\ValueProviders\ExchangeRateProviderValueProvider;
use App\Domain\Settings\ValueProviders\RangeValueProvider;
use App\Domain\Settings\ValueProviders\UserValueProvider;
use App\Domain\ExchangeRate\Enum\EventFrequencyEnum;

return [
    'table' => 'system_settings',

    'public' => [
        'google_recaptcha_enabled',
        'base_currency',
    ],

    'value_providers' => [
        'base_currency' => [
            'provider' => CurrencyValueProvider::class,
        ],
        'password_expiry_notification' => [
            'provider' => RangeValueProvider::class,
            'with' => [
                'start' => 7,
                'end' => 30,
                'label' => ':value Days Before Expiration',
            ],
        ],
        'notification_time' => [
            'provider' => RangeValueProvider::class,
            'with' => [
                'start' => 1,
                'end' => 3,
                'label' => ':value Week Before Closing Date|:value Weeks Before Closing Date',
            ],
        ],
        'failure_report_recipients' => [
            'provider' => UserValueProvider::class,
        ],
        'price_calculation_duration' => [
            'provider' => EnumValueProvider::class,
            'with' => [
                'enum' => PriceCalculationDurationEnum::class,
            ],
        ],
        'pipeliner_sync_schedule' => [
            'provider' => RangeValueProvider::class,
            'with' => [
                'start' => 1,
                'end' => 12,
                'label' => 'Every Hour|Every :value Hours',
            ],
        ],
        'file_upload_size' => [
            'provider' => RangeValueProvider::class,
            'with' => [
                'start' => 2,
                'end' => 10,
                'label' => ':value MB',
            ],
        ],
        'exchange_rate_provider' => [
            'provider' => ExchangeRateProviderValueProvider::class,
        ],
        'exchange_rate_update_schedule' => [
            'provider' => EnumValueProvider::class,
            'with' => [
                'enum' => EventFrequencyEnum::class,
            ],
        ],
        'maintenance_start_time' => [
            'provider' => RangeValueProvider::class,
            'with' => [
                'start' => 1,
                'end' => 30,
                'label' => ':value minute from now|:value minutes from now',
            ],
        ],
        'maintenance_end_time' => [
            'provider' => RangeValueProvider::class,
            'with' => [
                'start' => 5,
                'end' => 120,
                'step' => 5,
                'label' => ':value minutes',
            ],
        ],
    ],
];
