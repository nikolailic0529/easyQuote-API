<?php

return [
    'titles' => [
        'base_currency'                 => 'Base Currency',
        'file_upload_size'              => 'File Upload Size',
        'price_calculation_duration'    => 'Price Calculation Duration',
        'notification_time'             => 'Notification Time',
        'exchange_rates_update'         => 'Exchange Rates Update',
        'supported_file_types'          => 'Supported File Types',
        'failure_report_recipients'     => 'Failure Report Recipients',
        'password_expiry_notification'  => 'Password Expiry Notification',
        'exchange_rate_update_schedule' => 'Update Rates Schedule',
        'exchange_rate_provider'        => 'Exchange Rate Provider',
        'default_exchange_rate_margin'  => 'Default Exchange Rate Margin',
        'maintenance_start_time'        => 'Maintenance Start Time',
        'maintenance_end_time'          => 'Maintenance End Time',
        'maintenance_message'           => 'Maintenance Message',
        'google_recaptcha_enabled'      => 'Google Recaptcha Enabled',
        'use_legacy_doc_parsing_method' => 'Legacy Document Parsing',
        'mail_limit'                    => 'Monthly Mail Limit',
        'remaining_mail_limit'          => 'Remaining Mail Limit',
        'pipeliner_sync_schedule'       => 'Pipeliner Sync Schedule',
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
        ]
    ]
];
