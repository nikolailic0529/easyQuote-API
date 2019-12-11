<?php

return [
    'system_handle' => 'System Handle',
    'types' => ['New', 'Renewal'],
    'stages' => [
        'Initiated' => 1,
        'Import' => 20,
        'Mapping' => 40,
        'Review' => 50,
        'Margin' => 70,
        'Discount' => 90,
        'Additional Detail' => 99,
        'Complete' => 100
    ],
    'required_fields_exception' => 'Required Fields are missing.',
    'no_found_rfq_exception' => 'Sorry, no data found by this RFQ number.',
    'exists_same_rfq_submitted_quote' => 'An activated submitted Quote with the same RFQ number already exists.'
];
