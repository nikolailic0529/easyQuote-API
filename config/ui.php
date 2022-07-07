<?php

return [
    'path' => env('UI_PATH', '/var/www/easyQuote-UI'),
    'maintenance_data_path' => 'maintenance/maintenance_data.json',
    'routes' => [
        'quotes.submitted.listing' => 'importer/manage-submitted',
        'quotes.submitted.review' => 'importer/submitted-view/{quote}',
        'quotes.drafted.listing' => 'importer/manage',
        'quotes.drafted.review' => 'importer/quote-review/{quote}/false',
        'quotes.status' => 'importer/quote-status/{quote}',

        'contracts.submitted.review' => 'contracts/view/{contract}/submit',

        'customers.listing' => 'importer/customers',

        'users.profile' => 'users/profile',
        'users.notifications' => 'users/notification',

        'opportunity.update' => 'worldwide/update-opportunity/{opportunity}/list',
    ],
];
