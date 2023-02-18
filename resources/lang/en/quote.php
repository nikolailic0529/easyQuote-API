<?php

return [
    'system_handle' => 'System Handle',
    'types' => ['New', 'Renewal'],
    'stages' => [
        'Initiated' => 1,
        'Customer' => 10,
        'Import' => 20,
        'Mapping' => 40,
        'Review' => 50,
        'Margin' => 70,
        'Discount' => 90,
        'Additional Detail' => 99,
        'Complete' => 100,
    ],
];
