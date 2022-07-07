<?php

return [

    'webhook' => [

        'event_handlers' => [
            \App\Services\Pipeliner\Webhook\EventHandlers\AccountEventHandler::class,
            \App\Services\Pipeliner\Webhook\EventHandlers\AppointmentEventHandler::class,
            \App\Services\Pipeliner\Webhook\EventHandlers\ContactEventHandler::class,
            \App\Services\Pipeliner\Webhook\EventHandlers\TaskEventHandler::class,
        ],

        'options' => [
            'skip_keys' => [
                'easyQuote-integration',
            ],
        ],

    ],

    'sync' => [

        'schedule' => [
            'enabled' => env('PIPELINER_SYNC_SCHEDULE_ENABLED', true),
        ],

        /**
         * The allowed methods for synchronization.
         * Supported Methods: "pull", "push". Use '*' to mean all methods.
         */
        'allowed_methods' => explode(',', env('PIPELINER_SYNC_ALLOWED_METHODS', '*')),

        /**
         * The strategies EQ will use to synchronize entities between Pipeliner CRM & itself.
         */
        'strategies' => [
            'PushCompanyStrategy' => \App\Services\Pipeliner\Strategies\PushCompanyStrategy::class,
            'PullCompanyStrategy' => \App\Services\Pipeliner\Strategies\PullCompanyStrategy::class,
            'PushOpportunityStrategy' => \App\Services\Pipeliner\Strategies\PushOpportunityStrategy::class,
            'PullOpportunityStrategy' => \App\Services\Pipeliner\Strategies\PullOpportunityStrategy::class,
            'PushTaskStrategy' => \App\Services\Pipeliner\Strategies\PushTaskStrategy::class,
            'PullTaskStrategy' => \App\Services\Pipeliner\Strategies\PullTaskStrategy::class,
            'PushAppointmentStrategy' => \App\Services\Pipeliner\Strategies\PushAppointmentStrategy::class,
            'PullAppointmentStrategy' => \App\Services\Pipeliner\Strategies\PullAppointmentStrategy::class,
            'PushNoteStrategy' => \App\Services\Pipeliner\Strategies\PushNoteStrategy::class,
            'PullNoteStrategy' => \App\Services\Pipeliner\Strategies\PullNoteStrategy::class,
            'PushCustomFieldStrategy' => \App\Services\Pipeliner\Strategies\PushCustomFieldStrategy::class,
            'PullCustomFieldStrategy' => \App\Services\Pipeliner\Strategies\PullCustomFieldStrategy::class,
        ],

        'default_strategies' => [
            'PushOpportunityStrategy',
            'PullOpportunityStrategy',
        ],

        /**
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
            ],

        ],

    ],

    'custom_fields' => [

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
