<?php

return [
    'privileges' => [
        $R = 'Read Only',
        $CRU = 'Read & Write',
        $CRUD = 'Read, Write and Delete',
    ],
    'properties' => [
        'download_quote_pdf',
        'download_quote_price',
        'download_quote_schedule',
        'download_contract_pdf',
        'download_hpe_contract_pdf',

        'download_ww_quote_pdf',
        'download_ww_quote_distributor_file',
        'download_ww_quote_payment_schedule',
        'download_sales_order_pdf',

        'cancel_sales_orders',
    ],
    'submodules' => [
        'Quotes' => [
            'External Quotes' => [
                $R => [
                    'view_own_external_quotes',
                ],
                $CRU => [
                    'view_own_external_quotes',
                    'create_external_quotes',
                    'update_own_external_quotes',
                ],
                $CRUD => [
                    'view_own_external_quotes',
                    'create_external_quotes',
                    'update_own_external_quotes',
                    'delete_own_external_quotes',
                ],
            ],
            'Internal Quotes' => [
                $R => [
                    'view_own_internal_quotes',
                ],
                $CRU => [
                    'view_own_internal_quotes',
                    'create_internal_quotes',
                    'update_own_internal_quotes',
                ],
                $CRUD => [
                    'view_own_internal_quotes',
                    'create_internal_quotes',
                    'update_own_internal_quotes',
                    'delete_own_internal_quotes',
                ],
            ],
        ],
        'Contracts' => [
            'HPE Contracts' => [
                $R => [
                    'view_own_hpe_contracts',
                ],
                $CRU => [
                    'view_own_hpe_contracts',
                    'create_hpe_contracts',
                    'update_own_hpe_contracts',
                ],
                $CRUD => [
                    'view_own_hpe_contracts',
                    'create_hpe_contracts',
                    'update_own_hpe_contracts',
                    'delete_own_hpe_contracts',
                ],
            ],
            'Quote Based Contracts' => [
                $R => [
                    'view_own_quote_contracts',
                ],
                $CRU => [
                    'view_own_quote_contracts',
                    'create_quote_contracts',
                    'update_own_quote_contracts',
                ],
                $CRUD => [
                    'view_own_quote_contracts',
                    'create_quote_contracts',
                    'update_own_quote_contracts',
                    'delete_own_quote_contracts',
                ],
            ],
        ],
        'Templates' => [
            'Quote Templates' => [
                $R => [
                    'view_own_quote_templates',
                ],
                $CRU => [
                    'view_own_quote_templates',
                    'create_quote_templates',
                    'update_own_quote_templates',
                ],
                $CRUD => [
                    'view_own_quote_templates',
                    'create_quote_templates',
                    'update_own_quote_templates',
                    'delete_own_quote_templates',
                ],
            ],
            'Contract Templates' => [
                $R => [
                    'view_own_contract_templates',
                ],
                $CRU => [
                    'view_own_contract_templates',
                    'create_contract_templates',
                    'update_own_contract_templates',
                ],
                $CRUD => [
                    'view_own_contract_templates',
                    'create_contract_templates',
                    'update_own_contract_templates',
                    'delete_own_contract_templates',
                ],
            ],
            'HPE Contract Templates' => [
                $R => [
                    'view_own_hpe_contract_templates',
                ],
                $CRU => [
                    'view_own_hpe_contract_templates',
                    'create_hpe_contract_templates',
                    'update_own_hpe_contract_templates',
                ],
                $CRUD => [
                    'view_own_hpe_contract_templates',
                    'create_hpe_contract_templates',
                    'update_own_hpe_contract_templates',
                    'delete_own_hpe_contract_templates',
                ],
            ],
            'Quote Task Template' => [
                $R => [
                    'view_quote_task_template',
                ],
                $CRU => [
                    'view_quote_task_template',
                    'update_quote_task_template',
                ],
            ],
            'Opportunity Form Template' => [
                $R => [
                    'view_opportunity_form_template',
                ],
                $CRU => [
                    'view_opportunity_form_template',
                    'update_opportunity_form_template',
                ],
            ],
        ],
        'Users' => [
            'Teams' => [
                $R => [
                    'view_teams'
                ],
                $CRU => [
                    'view_teams', 'create_teams', 'update_teams'
                ],
                $CRUD => [
                    'view_teams', 'create_teams', 'update_teams', 'delete_teams'
                ]
            ],
            'Roles' => [
                $R => [
                    'view_roles',
                ],
                $CRU => [
                    'view_roles',
                    'create_roles',
                    'update_roles',
                ],
                $CRUD => [
                    'view_roles',
                    'create_roles',
                    'update_roles',
                    'delete_roles',
                ],
            ],
            'Invitations' => [
                $R => [
                    'view_invitations',
                ],
                $CRU => [
                    'view_invitations',
                    'create_invitations',
                    'update_invitations',
                ],
                $CRUD => [
                    'view_invitations',
                    'create_invitations',
                    'update_invitations',
                    'delete_invitations',
                ],
            ],
            'Teams' => [
                $R => [
                    'view_teams'
                ],
                $CRU => [
                    'view_teams', 'create_teams', 'update_teams'
                ],
                $CRUD => [
                    'view_teams', 'create_teams', 'update_teams', 'delete_teams'
                ]
            ],
        ],
        'Discounts' => [
            'Special Negotiation Discounts' => [
                $R => [
                    'view_sn_discounts',
                ],
                $CRU => [
                    'view_sn_discounts',
                    'create_sn_discounts',
                    'update_sn_discounts',
                ],
                $CRUD => [
                    'view_sn_discounts',
                    'create_sn_discounts',
                    'update_sn_discounts',
                    'delete_sn_discounts',
                ],
            ],
            'Promotional Discounts' => [
                $R => [
                    'view_promo_discounts',
                ],
                $CRU => [
                    'view_promo_discounts',
                    'create_promo_discounts',
                    'update_promo_discounts',
                ],
                $CRUD => [
                    'view_promo_discounts',
                    'create_promo_discounts',
                    'update_promo_discounts',
                    'delete_promo_discounts',
                ],
            ],
            'Pre-Pay Discounts' => [
                $R => [
                    'view_prepay_discounts',
                ],
                $CRU => [
                    'view_prepay_discounts',
                    'create_prepay_discounts',
                    'update_prepay_discounts',
                ],
                $CRUD => [
                    'view_prepay_discounts',
                    'create_prepay_discounts',
                    'update_prepay_discounts',
                    'delete_prepay_discounts',
                ],
            ],
            'Multi-Year Discounts' => [
                $R => [
                    'view_multiyear_discounts',
                ],
                $CRU => [
                    'view_multiyear_discounts',
                    'create_multiyear_discounts',
                    'update_multiyear_discounts',
                ],
                $CRUD => [
                    'view_multiyear_discounts',
                    'create_multiyear_discounts',
                    'update_multiyear_discounts',
                    'delete_multiyear_discounts',
                ],
            ],
        ],
    ],
    'modules' => [
        'Addresses' => [
            $R => [
                'view_addresses',
            ],
            $CRU => [
                'view_addresses',
                'create_addresses', 'update_addresses',
            ],
            $CRUD => [
                'view_addresses',
                'create_addresses', 'update_addresses', 'delete_addresses',
            ],
        ],
        'Contacts' => [
            $R => [
                'view_contacts',
            ],
            $CRU => [
                'view_contacts',
                'create_contacts', 'update_contacts',
            ],
            $CRUD => [
                'view_contacts',
                'create_contacts', 'update_contacts', 'delete_contacts',
            ],
        ],
        'Assets' => [
            $R => [
                'view_assets',
            ],
            $CRU => [
                'view_assets',
                'create_assets', 'update_assets',
            ],
            $CRUD => [
                'view_assets',
                'create_assets', 'update_assets', 'delete_assets',
            ],
        ],
        'Settings' => [
            $R => [
                'view_system_settings',
            ],
            $CRU => [
                'view_system_settings',
                'update_system_settings',
            ],
        ],
        'Audit' => [
            $R => [
                'view_activities',
            ],
        ],
        'Users' => [
            $R => [
                'view_users',
            ],
            $CRU => [
                'view_users',
                'invite_collaboration_users', 'update_users', 'reset_users_password',
            ],
            $CRUD => [
                'view_users',
                'invite_collaboration_users', 'update_users', 'reset_users_password',
                'delete_users',
            ],
        ],
        'Opportunities' => [
            $R => [
                'view_opportunities',
            ],
            $CRU => [
                'view_opportunities',
                'create_opportunities', 'update_own_opportunities',
            ],
            $CRUD => [
                'view_opportunities',
                'create_opportunities', 'update_own_opportunities',
                'delete_own_opportunities',
            ],
        ],
        'Worldwide Quotes' => [
            $R => [
                'view_own_ww_quotes', 'view_own_ww_quote_files',
            ],
            $CRU => [
                'view_own_ww_quotes', 'view_own_ww_quote_files',
                'create_ww_quotes', 'update_own_ww_quotes',
                'create_ww_quote_files', 'update_own_ww_quote_files', 'handle_own_ww_quote_files',
            ],
            $CRUD => [
                'view_own_ww_quotes', 'view_own_ww_quote_files',
                'create_ww_quotes', 'update_own_ww_quotes',
                'create_ww_quote_files', 'update_own_ww_quote_files', 'handle_own_ww_quote_files',
                'delete_own_ww_quotes', 'delete_own_ww_quote_files',
            ],
        ],
        'Sales Orders' => [
            $R => [
                'view_own_sales_orders'
            ],
            $CRU => [
                'view_own_sales_orders', 'create_sales_orders', 'update_own_sales_orders'
            ],
            $CRUD => [
                'view_own_sales_orders', 'create_sales_orders', 'update_own_sales_orders', 'delete_own_sales_orders'
            ],
        ],
        'Quotes' => [
            $R => [
                'view_own_quotes', 'view_quote_files',
            ],
            $CRU => [
                'view_own_quotes',
                'view_quote_files',
                'create_quotes', 'update_own_quotes',
                'create_quote_files', 'update_quote_files', 'handle_quote_files',
            ],
            $CRUD => [
                'view_own_quotes',
                'create_quotes', 'update_own_quotes', 'delete_own_quotes',
                'view_quote_files',
                'create_quote_files', 'update_quote_files', 'handle_quote_files',
                'delete_quote_files',
            ],
        ],
        'Countries' => [
            $R => [
                'view_countries',
            ],
            $CRU => [
                'view_countries',
                'create_countries', 'update_countries',
            ],
            $CRUD => [
                'view_countries',
                'create_countries', 'update_countries', 'delete_countries',
            ],
        ],
        'Contracts' => [
            $R => [
                'view_own_contracts',
            ],
            $CRU => [
                'view_own_contracts',
                'create_contracts', 'update_own_contracts',
            ],
            $CRUD => [
                'view_own_contracts',
                'create_contracts', 'update_own_contracts', 'delete_own_contracts',
            ],
        ],
        'Templates' => [
            $R => [
                'view_templates',
            ],
            $CRU => [
                'view_templates',
                'create_templates', 'update_templates',
            ],
            $CRUD => [
                'view_templates',
                'create_templates', 'update_templates', 'delete_templates',
            ],
        ],
        'Companies' => [
            $R => [
                'view_companies',
            ],
            $CRU => [
                'view_companies',
                'create_companies', 'update_companies',
            ],
            $CRUD => [
                'view_companies',
                'create_companies', 'update_companies', 'delete_companies',
            ],
        ],
        'Vendors' => [
            $R => [
                'view_vendors',
            ],
            $CRU => [
                'view_vendors',
                'create_vendors', 'update_vendors',
            ],
            $CRUD => [
                'view_vendors',
                'create_vendors', 'update_vendors', 'delete_vendors',
            ],
        ],
        'Margins' => [
            $R => [
                'view_margins',
            ],
            $CRU => [
                'view_margins',
                'create_margins', 'update_margins',
            ],
            $CRUD => [
                'view_margins',
                'create_margins', 'update_margins', 'delete_margins',
            ],
        ],
        'Discounts' => [
            $R => [
                'view_discounts',
            ],
            $CRU => [
                'view_discounts',
                'create_discounts', 'update_discounts',
            ],
            $CRUD => [
                'view_discounts',
                'create_discounts', 'update_discounts', 'delete_discounts',
            ],
        ],
    ],
];
