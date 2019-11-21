<?php

return [
    'types' => [
        'Internal', 'External'
    ],
    'categories' => [
        'End User', 'Reseller', 'Business Partner'
    ],
    'exists_exception' => 'The company with the same Name or VAT already exists.',
    'system_updating_exception' => 'You could not update the system defined Company.',
    'system_deleting_exception' => 'You could not delete the system defined Company.',
    'in_use_deleting_exception' => 'You could not delete this Company because it is already in use.',
];
