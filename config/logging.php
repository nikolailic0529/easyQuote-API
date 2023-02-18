<?php

use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
            'ignore_exceptions' => false,
            'permission' => 0777
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('/logs/laravel.log'),
            'level' => 'debug',
            'permission' => 0777
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('/logs/laravel.log'),
            'level' => 'debug',
            'days' => 365,
            'permission' => 0777
        ],

        'http-requests' => [
            'driver' => 'daily',
            'path' => storage_path('/logs/http.log'),
            'level' => 'debug',
            'days' => 365,
            'permission' => 0777
        ],

        'document-processor' => [
            'driver' => 'daily',
            'path' => storage_path('/logs/document-processor.log'),
            'level' => 'debug',
            'days' => 365,
            'permission' => 0777
        ],

        'document-engine-api' => [
            'driver' => 'daily',
            'path' => storage_path('logs/document-engine-api.log'),
            'level' => 'debug',
            'days' => 365,
            'permission' => 0777
        ],

        'stats-calculation' => [
            'driver' => 'daily',
            'path' => storage_path('/logs/stats-calculation.log'),
            'level' => 'debug',
            'days' => 365,
            'permission' => 0777
        ],

        'geocoding' => [
            'driver' => 'daily',
            'path' => storage_path('/logs/geocoding.log'),
            'level' => 'debug',
            'days' => 365,
            'permission' => 0777
        ],

        'vendor-services' => [
          'driver' => 'daily',
            'path' => storage_path('/logs/vendor-services.log'),
            'level' => 'debug',
            'days' => 365,
            'permission' => 0777
        ],

        'vendor-services-requests' => [
            'driver' => 'daily',
            'path' => storage_path('/logs/vendor-services-requests.log'),
            'level' => 'debug',
            'days' => 365,
            'permission' => 0777,
        ],

        'sales-orders' => [
            'driver' => 'daily',
            'path' => storage_path('/logs/sales-orders.log'),
            'level' => 'debug',
            'days' => 365,
            'permission' => 0777
        ],

        'opportunities' => [
            'driver' => 'daily',
            'path' => storage_path('/logs/opportunities.log'),
            'level' => 'debug',
            'days' => 365,
            'permission' => 0777
        ],

        'pipeliner' => [
            'driver' => 'daily',
            'path' => storage_path('/logs/pipeliner.log'),
            'level' => 'debug',
            'days' => 365,
            'permission' => 0777
        ],

        'pipeliner-requests' => [
            'driver' => 'daily',
            'path' => storage_path('/logs/pipeliner-requests.log'),
            'level' => 'debug',
            'days' => 365,
            'permission' => 0777,
        ],

        'tasks' => [
            'driver' => 'daily',
            'path' => storage_path('/logs/tasks.log'),
            'level' => 'debug',
            'days' => 365,
            'permission' => 0777,
        ],

        'appointments' => [
            'driver' => 'daily',
            'path' => storage_path('/logs/appointments.log'),
            'level' => 'debug',
            'days' => 365,
            'permission' => 0777,
        ],

        'addresses' => [
            'driver' => 'daily',
            'path' => storage_path('/logs/addresses.log'),
            'level' => 'debug',
            'days' => 365,
            'permission' => 0777,
        ],

        'companies' => [
            'driver' => 'daily',
            'path' => storage_path('/logs/companies.log'),
            'level' => 'debug',
            'days' => 365,
            'permission' => 0777,
        ],

        'google-requests' => [
            'driver' => 'daily',
            'path' => storage_path('/logs/google-requests.log'),
            'level' => 'debug',
            'days' => 365,
            'permission' => 0777,
        ],

        'search' => [
            'driver' => 'daily',
            'path' => storage_path('/logs/search.log'),
            'level' => 'debug',
            'days' => 365,
            'permission' => 0777,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'stdout' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => \Bramus\Monolog\Formatter\ColoredLineFormatter::class,
            'formatter_with' => [
                'allowInlineLineBreaks' => true,
                'ignoreEmptyContextAndExtra' => true,
            ],
            'with' => [
                'stream' => 'php://stdout',
            ],
            'level' => 'debug',
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => 'debug',
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => 'debug',
        ],
    ],
];
