<?php

return [
    'routes' => [
        'quotes' => [
            'submitted' => [
                'listing' => 'importer/manage-submitted',
                'review' => 'importer/submitted-view/{quote}'
            ],
            'drafted' => [
                'listing' => 'importer/manage',
                'review' => 'importer/quote-review/{quote}/false'
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
