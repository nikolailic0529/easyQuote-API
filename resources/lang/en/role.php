<?php

return [
    'privileges' => [
        'Read Only',
        'Read & Write',
        'Read, Write and Delete'
    ],
    'modules' => [
        'Quotes' => [
            'Read Only' => [
                'view_quotes'
            ],
            'Read & Write' => [
                'create_quotes', 'update_quotes',
                'create_quote_files', 'update_quote_files', 'handle_quote_files',
            ],
            'Read, Write and Delete' => [
                'create_quotes', 'update_quotes', 'delete_quotes',
                'create_quote_files', 'update_quote_files', 'handle_quote_files', 'delete_quote_files'
            ]
        ],
        'Templates' => [
            'Read Only' => [
                'view_templates'
            ],
            'Read & Write' => [
                'create_templates', 'update_templates'
            ],
            'Read, Write and Delete' => [
                'create_templates', 'update_templates', 'delete_templates'
            ]
        ],
        'Companies' => [
            'Read Only' => [
                'view_companies'
            ],
            'Read & Write' => [
                'create_companies', 'update_companies'
            ],
            'Read, Write and Delete' => [
                'create_companies', 'update_companies', 'delete_companies'
            ]
        ],
        'Vendors' => [
            'Read Only' => [
                'view_vendors'
            ],
            'Read & Write' => [
                'create_vendors', 'update_vendors'
            ],
            'Read, Write and Delete' => [
                'create_vendors', 'update_vendors', 'delete_vendors'
            ]
        ],
        'Margins' => [
            'Read Only' => [
                'view_margins'
            ],
            'Read & Write' => [
                'create_margins', 'update_margins'
            ],
            'Read, Write and Delete' => [
                'create_margins', 'update_margins', 'delete_margins'
            ]
        ],
        'Discounts' => [
            'Read Only' => [
                'view_discounts'
            ],
            'Read & Write' => [
                'create_discounts', 'update_discounts'
            ],
            'Read, Write and Delete' => [
                'create_discounts', 'update_discounts', 'delete_discounts'
            ]
        ]
    ],
    'exists_exception' => 'The Role with the same Name already exists.',
    'system_updating_exception' => 'You could not update the system defined Role.',
    'system_deleting_exception' => 'You could not delete the system defined Role.'
];
