<?php

return [
    'privileges' => [
        'Read Only',
        'Read & Write',
        'Read, Write and Delete'
    ],
    'modules' => [
        'Quotes' => [
            'Read Only' => [],
            'Read & Write' => [
                'create_quotes', 'update_own_quotes',
                'create_quote_files', 'update_own_quote_files', 'handle_own_quote_files',
            ],
            'Read, Write and Delete' => [
                'create_quotes', 'update_own_quotes', 'delete_own_quotes',
                'create_quote_files', 'update_own_quote_files', 'handle_own_quote_files', 'delete_own_quote_files'
            ]
        ],
        'Templates' => [
            'Read Only' => [],
            'Read & Write' => [
                'create_templates', 'update_own_templates'
            ],
            'Read, Write and Delete' => [
                'create_templates', 'update_own_templates', 'delete_own_templates'
            ]
        ],
        'Companies' => [
            'Read Only' => [],
            'Read & Write' => [
                'create_companies', 'update_own_companies'
            ],
            'Read, Write and Delete' => [
                'create_companies', 'update_own_companies', 'delete_own_companies'
            ]
        ],
        'Vendors' => [
            'Read Only' => [],
            'Read & Write' => [
                'create_vendors', 'update_own_vendors'
            ],
            'Read, Write and Delete' => [
                'create_vendors', 'update_own_vendors', 'delete_own_vendors'
            ]
        ],
        'Margins' => [
            'Read Only' => [],
            'Read & Write' => [
                'create_margins', 'update_own_margins'
            ],
            'Read, Write and Delete' => [
                'create_margins', 'update_own_margins', 'delete_own_margins'
            ]
        ],
        'Discounts' => [
            'Read Only' => [],
            'Read & Write' => [
                'create_discounts', 'update_own_discounts'
            ],
            'Read, Write and Delete' => [
                'create_discounts', 'update_own_discounts', 'delete_own_discounts'
            ]
        ]
    ],
    'exists_exception' => 'The Role with the same Name already exists.',
    'system_updating_exception' => 'You could not update the system defined Role.',
    'system_deleting_exception' => 'You could not delete the system defined Role.'
];
