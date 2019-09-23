<?php

return [
    'no_handleable_file' => 'This file format is not available for handling',
    'not_schedule_exception' => 'This file doesn\'t look like a Payment Schedule',
    'separator_exception' => 'It seems you\'ve chosen wrong Data Select Separator',
    'quote_has_not_template_exception' => 'Before Set Template for the Quote',
    'unknown_column_header' => 'Unknown Header',
    'excel' => [
        'unreadable_file_exception' => "The given file isn't readable. Please try to re-save it."
    ],
    'word' => [
        'no_columns_exception' => 'Uploaded file has not any required columns'
    ],
    'pdf' => [
        'replacements' => [
            'search' => ["ER\x057g", "ERSvr", "UR\x057g", "UR\x00Svr", 'EU$ð', "ER\x00Svr"],
            'replace' => ["EU Svr", "EU Svr", "EU Svr", "EU Svr", "EU Svr"]
        ]
    ]
];
