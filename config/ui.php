<?php

return [
    'routes' => [
        'quotes' => [
            'submitted' => [
                'listing' => 'importer/manage-submitted',
                'review' => 'importer/submitted-view/{quote_id}'
            ],
            'drafted' => [
                'listing' => 'importer/manage',
                'review' => 'importer/quote-review/{quote_id}/false'
            ]
        ],
        'customers' => [
            'listing' => 'importer/customers'
        ]
    ]
];
