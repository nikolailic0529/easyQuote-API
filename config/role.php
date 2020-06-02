<?php

return [
    'properties' => [
        'download_quote_pdf',
        'download_quote_price',
        'download_quote_schedule',
        'download_contract_pdf',
    ],
    'privileges' => [
        'Read Only',
        'Read & Write',
        'Read, Write and Delete'
    ],
    'modules' => [
        'Addresses' => [
            'Read Only' => [
                'view_addresses'
            ],
            'Read & Write' => [
                'view_addresses',
                'create_addresses', 'update_addresses'
            ],
            'Read, Write and Delete' => [
                'view_addresses',
                'create_addresses', 'update_addresses', 'delete_addresses'
            ]
        ],
        'Contacts' => [
            'Read Only' => [
                'view_contacts'
            ],
            'Read & Write' => [
                'view_contacts',
                'create_contacts', 'update_contacts'
            ],
            'Read, Write and Delete' => [
                'view_contacts',
                'create_contacts', 'update_contacts', 'delete_contacts'
            ]
        ],
        'Assets' => [
            'Read Only' => [
                'view_assets'
            ],
            'Read & Write' => [
                'view_assets',
                'create_assets', 'update_assets'
            ],
            'Read, Write and Delete' => [
                'view_assets',
                'create_assets', 'update_assets', 'delete_assets'
            ]
        ],
        'Settings' => [
            'Read Only' => [
                'view_system_settings'
            ],
            'Read & Write' => [
                'view_system_settings',
                'update_system_settings'
            ]
        ],
        'Audit' => [
            'Read Only' => [
                'view_activities'
            ]
        ],
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
                'view_own_quotes', 'view_quote_files'
            ],
            'Read & Write' => [
                'view_own_quotes',
                'view_quote_files',
                'create_quotes', 'update_own_quotes',
                'create_quote_files', 'update_quote_files', 'handle_quote_files',
            ],
            'Read, Write and Delete' => [
                'view_own_quotes',
                'create_quotes', 'update_own_quotes', 'delete_own_quotes',
                'view_quote_files',
                'create_quote_files', 'update_quote_files', 'handle_quote_files',
                'delete_quote_files'
            ]
        ],
        'Countries' => [
            'Read Only' => [
                'view_countries'
            ],
            'Read & Write' => [
                'view_countries',
                'create_countries', 'update_countries'
            ],
            'Read, Write and Delete' => [
                'view_countries',
                'create_countries', 'update_countries', 'delete_countries'
            ]
        ],
        'Contracts' => [
            'Read Only' => [
                'view_own_contracts'
            ],
            'Read & Write' => [
                'view_own_contracts',
                'create_contracts', 'update_own_contracts'
            ],
            'Read, Write and Delete' => [
                'view_own_contracts',
                'create_contracts', 'update_own_contracts', 'delete_own_contracts'
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
    ]
];
