<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, SparkPost and others. This file provides a sane default
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'search' => [
        'enabled' => env('ELASTICSEARCH_ENABLED', false),
        'hosts' => explode(',', env('ELASTICSEARCH_HOSTS')),
    ],

    'slack' => [
        'enabled' => env('SLACK_ENABLED', false),
        'endpoint' => env('SLACK_SERVICE_URL', SLACK_SERVICE_URL),
    ],

    'recaptcha' => [
        'skip_key' => env('RECAPTCHA_SKIP_KEY'),
        'skip_enabled' => env('RECAPTCHA_SKIP_ENABLED', false)
    ],

    'recaptcha_v3' => [
        'url' => env('RECAPTCHA_V3_URL', 'https://www.google.com/recaptcha/api/siteverify'),
        'key' => env('RECAPTCHA_V3_KEY'),
        'secret' => env('RECAPTCHA_V3_SECRET'),
    ],

    'recaptcha_v2' => [
        'url' => env('RECAPTCHA_V2_URL', 'https://www.google.com/recaptcha/api/siteverify'),
        'key' => env('RECAPTCHA_V2_KEY'),
        'secret' => env('RECAPTCHA_V2_SECRET'),
    ],

    'vs' => [
        'url' => env('VS_API_URL'),

        'token_route' => 'api/oauth/token',

        'submit_sales_order_route' => 'bc-data',

        'service_routes' => [
            'DEL' => 'dell-data/{serial}',
            'HPE' => 'hpe-data/serial/{serial}/sku/{sku}/country/{country}',
            'LEN' => 'lenovo-data/serial/{serial}/type/{sku}',
            'IBM' => 'ibm-data/serial/{serial}/type/{sku}',
        ],

        'support_lookup_routes' => [
            'HPE' => 'hpe-data/sku/{sku}/country/{country}',
            'LEN' => 'lenovo-data/sku/{sku}/country/{country}/currency/{currency}'
        ],

        'client_id' => env('VS_API_CLIENT_ID'),
        'client_secret' => env('VS_API_CLIENT_SECRET'),
    ],

    'document_api' => [
        'url' => env('DOCUMENT_API_URL'),
        'client_username' => env('DOCUMENT_API_CLIENT_USERNAME'),
        'client_password' => env('DOCUMENT_API_CLIENT_PASSWORD'),
    ],

];
