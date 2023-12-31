<?php

return [
    'webhook' => [
        'event_handlers' => [
            \App\Domain\Pipeliner\Services\Webhook\EventHandlers\AccountEventHandler::class,
            \App\Domain\Pipeliner\Services\Webhook\EventHandlers\AccountRelationEventHandler::class,
            \App\Domain\Pipeliner\Services\Webhook\EventHandlers\AppointmentEventHandler::class,
            \App\Domain\Pipeliner\Services\Webhook\EventHandlers\ContactEventHandler::class,
            \App\Domain\Pipeliner\Services\Webhook\EventHandlers\TaskEventHandler::class,
            \App\Domain\Pipeliner\Services\Webhook\EventHandlers\OpportunityEventHandler::class,
        ],

        'options' => [
            'skip_keys' => [
                'eq_integration',
            ],
        ],
    ],

    'client' => [
        'throttle' => [
            'rpm' => 100_000 / 5,
            'max_connections' => 10,
        ],
    ],

    'sync' => [
        'schedule' => [
            'enabled' => env('PIPELINER_SYNC_SCHEDULE_ENABLED', true),
        ],

        /*
         * The allowed methods for synchronization.
         * Supported Methods: "pull", "push". Use '*' to mean all methods.
         */
        'allowed_methods' => explode(',', env('PIPELINER_SYNC_ALLOWED_METHODS', '*')),

        /*
         * The allowed sales units for synchronization.
         * Use '*' to mean all sales units.
         */
        'allowed_sales_units' => explode(',', env('PIPELINER_SYNC_ALLOWED_UNITS', '*')),

        /*
         * The strategies EQ will use to synchronize entities between Pipeliner CRM & itself.
         */
        'strategies' => [
            'PushCompanyStrategy' => \App\Domain\Pipeliner\Services\Strategies\PushCompanyStrategy::class,
            'PullCompanyStrategy' => \App\Domain\Pipeliner\Services\Strategies\PullCompanyStrategy::class,
            'PushOpportunityStrategy' => \App\Domain\Pipeliner\Services\Strategies\PushOpportunityStrategy::class,
            'PullOpportunityStrategy' => \App\Domain\Pipeliner\Services\Strategies\PullOpportunityStrategy::class,
            'PushTaskStrategy' => \App\Domain\Pipeliner\Services\Strategies\PushTaskStrategy::class,
            'PullTaskStrategy' => \App\Domain\Pipeliner\Services\Strategies\PullTaskStrategy::class,
            'PushAppointmentStrategy' => \App\Domain\Pipeliner\Services\Strategies\PushAppointmentStrategy::class,
            'PullAppointmentStrategy' => \App\Domain\Pipeliner\Services\Strategies\PullAppointmentStrategy::class,
            'PushNoteStrategy' => \App\Domain\Pipeliner\Services\Strategies\PushNoteStrategy::class,
            'PullNoteStrategy' => \App\Domain\Pipeliner\Services\Strategies\PullNoteStrategy::class,
            'PushAttachmentStrategy' => \App\Domain\Pipeliner\Services\Strategies\PushAttachmentStrategy::class,
            'PullAttachmentStrategy' => \App\Domain\Pipeliner\Services\Strategies\PullAttachmentStrategy::class,
            'PushCustomFieldStrategy' => \App\Domain\Pipeliner\Services\Strategies\PushCustomFieldStrategy::class,
            'PullCustomFieldStrategy' => \App\Domain\Pipeliner\Services\Strategies\PullCustomFieldStrategy::class,
            'PushSalesUnitStrategy' => \App\Domain\Pipeliner\Services\Strategies\PushSalesUnitStrategy::class,
            'PullSalesUnitStrategy' => \App\Domain\Pipeliner\Services\Strategies\PullSalesUnitStrategy::class,
            'PushCurrencyStrategy' => \App\Domain\Pipeliner\Services\Strategies\PushCurrencyStrategy::class,
        ],

        'aggregate_strategies' => [
            'PullOpportunityStrategy',
            'PushOpportunityStrategy',
            'PullCompanyStrategy',
            'PushCompanyStrategy',
        ],

        'default_strategies' => [
            'PushOpportunityStrategy',
            'PullOpportunityStrategy',
        ],

        /*
         * The email of client used as default entity.
         */
        'default_client_email' => env('PIPELINER_SYNC_DEFAULT_CLIENT_EMAIL', 'fakhar.anwar@europlusdirect.com'),

        'custom_fields' => [
            'enabled' => env('PIPELINER_SYNC_CUSTOM_FIELDS_ENABLED', true),

            'mapping' => [
                'opportunity_country1' => 'cf_country6_id',
                'opportunity_country2' => 'cf_distributor_country2n1_id',
                'opportunity_country3' => 'cf_distributor_country3n_id',
                'opportunity_country4' => 'cf_distributor_country4n_id',
                'opportunity_country5' => 'cf_distributor_country5n_id',

                'opportunity_distributor1' => 'cf_distributor1_id',
                'opportunity_distributor2' => 'cf_distributor2n_id',
                'opportunity_distributor3' => 'cf_distributor3n_id',
                'opportunity_distributor4' => 'cf_distributor4n_id',
                'opportunity_distributor5' => 'cf_distributor5n_id',

                'opportunity_personal_rating' => 'cf_personal_rating_id',
            ],
        ],
    ],

    'custom_fields' => [
        /*
         * Used for country name normalization.
         */
        'country_field_aliases' => [
            'UK' => 'United Kingdom',
        ],

        'vendor_code_option_name' => [
            'ARU' => 'Aruba',
            'CIS' => 'Cisco',
            'DEL' => 'Dell',
            'FUJ' => 'Fujitsu',
            'HPE' => 'HPE',
            'IBM' => 'IBM',
            'LEN' => 'Lenovo',
            'VMW' => 'VM Ware',
        ],

        'country_option_iso_3166_2' => [
            'Abu Dhabi' => 'AE',
            'Africa' => 'CF',
            'Brunei' => 'BN',
            'Liechtenstein ' => 'LI',
            'Macedonia' => 'MK',
            'Russia' => 'RU',
            'South Korea' => 'KR',
            'Taiwan' => 'TW',
            'UAE' => 'AE',
            'UK' => 'GB',
            'USA' => 'US',
            'Vietnam' => 'VN',
        ],

        'suppliers' => [
            [
                'country_id' => 'cfCountry6Id',
                'distributor_id' => 'cfDistributor1Id',
                'contact_name' => 'cfContactName',
                'email_address' => 'cfEmailAddress',
            ],
            [
                'country_id' => 'cfDistributorCountry2n1Id',
                'distributor_id' => 'cfDistributor2nId',
                'contact_name' => 'cfContactName1',
                'email_address' => 'cfEmailAddress1',
            ],
            [
                'country_id' => 'cfDistributorCountry3nId',
                'distributor_id' => 'cfDistributor3nId',
                'contact_name' => 'cfContactName2',
                'email_address' => 'cfEmailAddress2',
            ],
            [
                'country_id' => 'cfDistributorCountry4nId',
                'distributor_id' => 'cfDistributor4nId',
                'contact_name' => 'cfContactName3',
                'email_address' => 'cfEmailAddress3',
            ],
            [
                'country_id' => 'cfDistributorCountry5nId',
                'distributor_id' => 'cfDistributor5nId',
                'contact_name' => 'cfContactName4',
                'email_address' => 'cfEmailAddress4',
            ],
        ],
    ],
];
