<?php

return [
    'titles' => [
        'base_currency' => 'Base Currency',
        'file_upload_size' => 'File Upload Size',
        'price_calculation_duration' => 'Price Calculation Duration',
        'notification_time' => 'Notification Time',
        'exchange_rates_update' => 'Exchange Rates Update',
        'supported_file_types' => 'Supported File Types',
        'failure_report_recipients' => 'Failure Report Recipients'
    ],
    'supported_file_types' => [
        'pdf' => [
            'application/pdf'
        ],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ],
        'xlsx' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ],
        'csv' => [
            'text/csv',
            'text/plain',
            'application/vnd.ms-excel',
        ]
    ]
];
