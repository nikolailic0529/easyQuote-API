<?php

return [
    'path' => env('UI_PATH', '/var/www/easyQuote-UI'),
    'maintenance_data_path' => 'maintenance/maintenance_data.json',
    'routes' => [
        'quotes' => [
            'submitted' => [
                'listing' => 'importer/manage-submitted',
                'review' => 'importer/submitted-view/{quote}'
            ],
            'drafted' => [
                'listing' => 'importer/manage',
                'review' => 'importer/quote-review/{quote}/false'
            ],
            'status' => 'importer/quote-status/{quote}'
        ],
        'contracts' => [
            'submitted' => [
                'review' => 'contracts/view/{contract}/submit'
            ]
        ],
        'customers' => [
            'listing' => 'importer/customers'
        ],
        'users' => [
            'profile' => 'users/profile',
            'notifications' => 'users/notification'
        ]
    ]
];
