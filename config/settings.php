<?php

use App\Domain\ExchangeRate\Enum\EventFrequencyEnum;
use App\Domain\Settings\Enum\PriceCalculationDurationEnum;
use App\Domain\Settings\ValueProviders\CurrencyValueProvider;
use App\Domain\Settings\ValueProviders\EnumValueProvider;
use App\Domain\Settings\ValueProviders\ExchangeRateProviderValueProvider;
use App\Domain\Settings\ValueProviders\RangeValueProvider;
use App\Domain\Settings\ValueProviders\UserValueProvider;

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
                'label' => 'settings.value_labels.password_expiry_notification',
            ],
        ],
        'notification_time' => [
            'provider' => RangeValueProvider::class,
            'with' => [
                'start' => 1,
                'end' => 3,
                'label' => 'settings.value_labels.notification_time',
            ],
        ],
        'failure_report_recipients' => [
            'provider' => UserValueProvider::class,
        ],
        'price_calculation_duration' => [
            'provider' => EnumValueProvider::class,
            'with' => [
                'enum' => PriceCalculationDurationEnum::class,
                'label' => 'settings.value_labels.price_calculation_duration',
            ],
        ],
        'pipeliner_sync_schedule' => [
            'provider' => RangeValueProvider::class,
            'with' => [
                'start' => 1,
                'end' => 12,
                'label' => 'settings.value_labels.pipeliner_sync_schedule',
            ],
        ],
        'file_upload_size' => [
            'provider' => RangeValueProvider::class,
            'with' => [
                'start' => 2,
                'end' => 10,
                'label' => 'settings.value_labels.file_upload_size',
            ],
        ],
        'exchange_rate_provider' => [
            'provider' => ExchangeRateProviderValueProvider::class,
        ],
        'exchange_rate_update_schedule' => [
            'provider' => EnumValueProvider::class,
            'with' => [
                'enum' => EventFrequencyEnum::class,
                'label' => 'settings.value_labels.exchange_rate_update_schedule',
            ],
        ],
        'maintenance_start_time' => [
            'provider' => RangeValueProvider::class,
            'with' => [
                'start' => 1,
                'end' => 30,
                'label' => 'settings.value_labels.maintenance_start_time',
            ],
        ],
        'maintenance_end_time' => [
            'provider' => RangeValueProvider::class,
            'with' => [
                'start' => 5,
                'end' => 120,
                'step' => 5,
                'label' => 'settings.value_labels.maintenance_end_time',
            ],
        ],
    ],
];
