<?php

return [
    'default' => [
        'email' => 'app@easyquote.com',
        'first_name' => 'easyQuote',
        'last_name' => 'Application',
        'timezone_abbr' => 'UTC',
    ],

    'password_expiration' => [
        'days' => env('USER_PASSWORD_EXPIRATION_DAYS', 30),
        'enabled' => env('USER_PASSWORD_EXPIRATION_ENABLED', true),
        'ignored_routes' => [
            'users.create',
            'account.update',
            'account.show',
            'account.logout',
            'signin',
        ],
    ],
];
