<?php

return [
    'privileges' => [
        'Read Only',
        'Read & Write',
        'Read, Write and Delete'
    ],
    'modules' => [
        'Users' => [
            'Read Only' => [
                'view_users'
            ],
            'Read & Write' => [
                'view_users',
                'invite_collaboration_users', 'update_users', 'reset_users_password'
            ],
            'Read, Write and Delete' => [
                'view_users',
                'invite_collaboration_users', 'update_users', 'reset_users_password',
                'delete_users'
            ]
        ],
        'Quotes' => [
            'Read Only' => [
                'view_own_quotes'
            ],
            'Read & Write' => [
                'view_own_quotes',
                'create_quotes', 'update_own_quotes',
                'create_quote_files', 'update_quote_files', 'handle_quote_files',
            ],
            'Read, Write and Delete' => [
                'view_own_quotes',
                'create_quotes', 'update_own_quotes', 'delete_own_quotes',
                'create_quote_files', 'update_quote_files', 'handle_quote_files',
                'delete_quote_files'
            ]
        ],
        'Templates' => [
            'Read Only' => [
                'view_templates'
            ],
            'Read & Write' => [
                'view_templates',
                'create_templates', 'update_templates'
            ],
            'Read, Write and Delete' => [
                'view_templates',
                'create_templates', 'update_templates', 'delete_templates'
            ]
        ],
        'Companies' => [
            'Read Only' => [
                'view_companies'
            ],
            'Read & Write' => [
                'view_companies',
                'create_companies', 'update_companies'
            ],
            'Read, Write and Delete' => [
                'view_companies',
                'create_companies', 'update_companies', 'delete_companies'
            ]
        ],
        'Vendors' => [
            'Read Only' => [
                'view_vendors'
            ],
            'Read & Write' => [
                'view_vendors',
                'create_vendors', 'update_vendors'
            ],
            'Read, Write and Delete' => [
                'view_vendors',
                'create_vendors', 'update_vendors', 'delete_vendors'
            ]
        ],
        'Margins' => [
            'Read Only' => [
                'view_margins'
            ],
            'Read & Write' => [
                'view_margins',
                'create_margins', 'update_margins'
            ],
            'Read, Write and Delete' => [
                'view_margins',
                'create_margins', 'update_margins', 'delete_margins'
            ]
        ],
        'Discounts' => [
            'Read Only' => [
                'view_discounts'
            ],
            'Read & Write' => [
                'view_discounts',
                'create_discounts', 'update_discounts'
            ],
            'Read, Write and Delete' => [
                'view_discounts',
                'create_discounts', 'update_discounts', 'delete_discounts'
            ]
        ]
    ],
    'exists_exception' => 'The Role with the same Name already exists.',
    'system_updating_exception' => 'You could not update the system defined Role.',
    'system_deleting_exception' => 'You could not delete the system defined Role.'
];
