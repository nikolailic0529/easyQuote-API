<?php

return [
    'privileges' => [
        $RO = 'Read Only',
        $RW = 'Read & Write',
        $RWED = 'Read & Write where Editor',
        $RWD = 'Read, Write and Delete',
    ],
    'properties' => [
        // Rescue quote
        'download_quote_pdf',
        'download_quote_price',
        'download_quote_schedule',

        // Contract
        'download_contract_pdf',
        'download_hpe_contract_pdf',

        // Worldwide quote
        'download_ww_quote_pdf',
        'download_ww_quote_distributor_file',
        'download_ww_quote_payment_schedule',
        'change_ww_quotes_ownership',

        // Sales order
        'download_sales_order_pdf',
        'cancel_sales_orders',
        'resubmit_sales_orders',
        'unravel_sales_orders',
        'alter_active_status_of_sales_orders',
        'refresh_status_of_sales_orders',

        // Opportunity
        'change_opportunities_ownership',

        // Company
        'change_companies_ownership',

        // Asset
        'change_assets_ownership',
    ],
    'submodules' => [
        'Quotes' => [
            'External Quotes' => [
                $RO => [
                    'view_own_external_quotes',
                ],
                $RW => [
                    'view_own_external_quotes',
                    'create_external_quotes',
                    'update_own_external_quotes',
                ],
                $RWD => [
                    'view_own_external_quotes',
                    'create_external_quotes',
                    'update_own_external_quotes',
                    'delete_own_external_quotes',
                ],
            ],
            'Internal Quotes' => [
                $RO => [
                    'view_own_internal_quotes',
                ],
                $RW => [
                    'view_own_internal_quotes',
                    'create_internal_quotes',
                    'update_own_internal_quotes',
                ],
                $RWD => [
                    'view_own_internal_quotes',
                    'create_internal_quotes',
                    'update_own_internal_quotes',
                    'delete_own_internal_quotes',
                    'delete_rfq',
                ],
            ],
        ],
        'Contracts' => [
            'HPE Contracts' => [
                $RO => [
                    'view_own_hpe_contracts',
                ],
                $RW => [
                    'view_own_hpe_contracts',
                    'create_hpe_contracts',
                    'update_own_hpe_contracts',
                ],
                $RWD => [
                    'view_own_hpe_contracts',
                    'create_hpe_contracts',
                    'update_own_hpe_contracts',
                    'delete_own_hpe_contracts',
                ],
            ],
            'Quote Based Contracts' => [
                $RO => [
                    'view_own_quote_contracts',
                ],
                $RW => [
                    'view_own_quote_contracts',
                    'create_quote_contracts',
                    'update_own_quote_contracts',
                ],
                $RWD => [
                    'view_own_quote_contracts',
                    'create_quote_contracts',
                    'update_own_quote_contracts',
                    'delete_own_quote_contracts',
                ],
            ],
        ],
        'Templates' => [
            'Quote Templates' => [
                $RO => [
                    'view_own_quote_templates',
                ],
                $RW => [
                    'view_own_quote_templates',
                    'create_quote_templates',
                    'update_own_quote_templates',
                ],
                $RWD => [
                    'view_own_quote_templates',
                    'create_quote_templates',
                    'update_own_quote_templates',
                    'delete_own_quote_templates',
                ],
            ],
            'Contract Templates' => [
                $RO => [
                    'view_own_contract_templates',
                ],
                $RW => [
                    'view_own_contract_templates',
                    'create_contract_templates',
                    'update_own_contract_templates',
                ],
                $RWD => [
                    'view_own_contract_templates',
                    'create_contract_templates',
                    'update_own_contract_templates',
                    'delete_own_contract_templates',
                ],
            ],
            'HPE Contract Templates' => [
                $RO => [
                    'view_own_hpe_contract_templates',
                ],
                $RW => [
                    'view_own_hpe_contract_templates',
                    'create_hpe_contract_templates',
                    'update_own_hpe_contract_templates',
                ],
                $RWD => [
                    'view_own_hpe_contract_templates',
                    'create_hpe_contract_templates',
                    'update_own_hpe_contract_templates',
                    'delete_own_hpe_contract_templates',
                ],
            ],
            'Sales Order Templates' => [
                $RO => [
                    'view_sales_order_templates',
                ],
                $RW => [
                    'view_sales_order_templates',
                    'create_sales_order_templates',
                    'update_own_sales_order_templates',
                ],
                $RWD => [
                    'view_sales_order_templates',
                    'create_sales_order_templates',
                    'update_own_sales_order_templates',
                    'delete_own_sales_order_templates',
                ],
            ],
            'Quote Task Template' => [
                $RO => [
                    'view_quote_task_template',
                ],
                $RW => [
                    'view_quote_task_template',
                    'update_quote_task_template',
                ],
            ],
            'Opportunity Forms' => [
                $RO => [
                    'view_opportunity_forms',
                ],
                $RW => [
                    'view_opportunity_forms',
                    'create_opportunity_forms', 'update_opportunity_forms',
                ],
                $RWD => [
                    'view_opportunity_forms',
                    'create_opportunity_forms', 'update_opportunity_forms',
                    'delete_opportunity_forms',
                ],
            ],
            'Importable Columns' => [
                $RO => [
                    'view_importable_columns',
                ],
                $RW => [
                    'view_importable_columns',
                    'create_importable_columns',
                    'update_importable_columns',
                ],
                $RWD => [
                    'view_importable_columns',
                    'create_importable_columns',
                    'update_importable_columns',
                    'delete_importable_columns',
                ],
            ],
        ],
        'Users' => [
            'Teams' => [
                $RO => [
                    'view_teams',
                ],
                $RW => [
                    'view_teams', 'create_teams', 'update_teams',
                ],
                $RWD => [
                    'view_teams', 'create_teams', 'update_teams', 'delete_teams',
                ],
            ],
            'Roles' => [
                $RO => [
                    'view_roles',
                ],
                $RW => [
                    'view_roles',
                    'create_roles',
                    'update_roles',
                ],
                $RWD => [
                    'view_roles',
                    'create_roles',
                    'update_roles',
                    'delete_roles',
                ],
            ],
            'Invitations' => [
                $RO => [
                    'view_invitations',
                ],
                $RW => [
                    'view_invitations',
                    'create_invitations',
                    'update_invitations',
                ],
                $RWD => [
                    'view_invitations',
                    'create_invitations',
                    'update_invitations',
                    'delete_invitations',
                ],
            ],
        ],
        'Discounts' => [
            'Special Negotiation Discounts' => [
                $RO => [
                    'view_sn_discounts',
                ],
                $RW => [
                    'view_sn_discounts',
                    'create_sn_discounts',
                    'update_sn_discounts',
                ],
                $RWD => [
                    'view_sn_discounts',
                    'create_sn_discounts',
                    'update_sn_discounts',
                    'delete_sn_discounts',
                ],
            ],
            'Promotional Discounts' => [
                $RO => [
                    'view_promo_discounts',
                ],
                $RW => [
                    'view_promo_discounts',
                    'create_promo_discounts',
                    'update_promo_discounts',
                ],
                $RWD => [
                    'view_promo_discounts',
                    'create_promo_discounts',
                    'update_promo_discounts',
                    'delete_promo_discounts',
                ],
            ],
            'Pre-Pay Discounts' => [
                $RO => [
                    'view_prepay_discounts',
                ],
                $RW => [
                    'view_prepay_discounts',
                    'create_prepay_discounts',
                    'update_prepay_discounts',
                ],
                $RWD => [
                    'view_prepay_discounts',
                    'create_prepay_discounts',
                    'update_prepay_discounts',
                    'delete_prepay_discounts',
                ],
            ],
            'Multi-Year Discounts' => [
                $RO => [
                    'view_multiyear_discounts',
                ],
                $RW => [
                    'view_multiyear_discounts',
                    'create_multiyear_discounts',
                    'update_multiyear_discounts',
                ],
                $RWD => [
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
            $RO => [
                'view_addresses',
            ],
            $RW => [
                'view_addresses',
                'create_addresses', 'update_addresses',
            ],
            $RWD => [
                'view_addresses',
                'create_addresses', 'update_addresses', 'delete_addresses',
            ],
        ],
        'Contacts' => [
            $RO => [
                'view_contacts',
            ],
            $RW => [
                'view_contacts',
                'create_contacts', 'update_contacts',
            ],
            $RWD => [
                'view_contacts',
                'create_contacts', 'update_contacts', 'delete_contacts',
            ],
        ],
        'Assets' => [
            $RO => [
                'view_assets',
            ],
            $RW => [
                'view_assets',
                'create_assets', 'update_assets',
            ],
            $RWD => [
                'view_assets',
                'create_assets', 'update_assets', 'delete_assets',
            ],
        ],
        'Settings' => [
            $RO => [
                'view_system_settings',
            ],
            $RW => [
                'view_system_settings',
                'update_system_settings',
            ],
        ],
        'Audit' => [
            $RO => [
                'view_activities',
            ],
        ],
        'Users' => [
            $RO => [
                'view_users',
            ],
            $RW => [
                'view_users',
                'invite_collaboration_users', 'update_users', 'reset_users_password',
            ],
            $RWD => [
                'view_users',
                'invite_collaboration_users', 'update_users', 'reset_users_password',
                'delete_users',
            ],
        ],
        'Opportunities' => [
            $RO => [
                'view_opportunities',
            ],
            $RW => [
                'view_opportunities',
                'create_opportunities', 'update_own_opportunities',
            ],
            $RWED => [
                'view_opportunities',
                'create_opportunities', 'update_own_opportunities',
                'view_opportunities_where_editor',
                'update_opportunities_where_editor',
            ],
            $RWD => [
                'view_opportunities',
                'create_opportunities', 'update_own_opportunities',
                'delete_own_opportunities',
            ],
        ],
        'Worldwide Quotes' => [
            $RO => [
                'view_own_ww_quotes', 'view_own_ww_quote_files',
            ],
            $RW => [
                'view_own_ww_quotes', 'view_own_ww_quote_files',
                'create_ww_quotes', 'update_own_ww_quotes',
                'create_ww_quote_files', 'update_own_ww_quote_files', 'handle_own_ww_quote_files',
            ],
            $RWED => [
                'view_own_ww_quotes', 'view_own_ww_quote_files',
                'create_ww_quotes', 'update_own_ww_quotes',
                'create_ww_quote_files', 'update_own_ww_quote_files', 'handle_own_ww_quote_files',

                'view_ww_quotes_where_editor',
                'update_ww_quotes_where_editor',
            ],
            $RWD => [
                'view_own_ww_quotes', 'view_own_ww_quote_files',
                'create_ww_quotes', 'update_own_ww_quotes',
                'create_ww_quote_files', 'update_own_ww_quote_files', 'handle_own_ww_quote_files',
                'delete_own_ww_quotes', 'delete_own_ww_quote_files',
            ],
        ],
        'Sales Orders' => [
            $RO => [
                'view_own_sales_orders',
            ],
            $RW => [
                'view_own_sales_orders', 'create_sales_orders', 'update_own_sales_orders',
            ],
            $RWD => [
                'view_own_sales_orders', 'create_sales_orders', 'update_own_sales_orders', 'delete_own_sales_orders',
            ],
        ],
        'Quotes' => [
            $RO => [
                'view_own_quotes', 'view_quote_files',
            ],
            $RW => [
                'view_own_quotes',
                'view_quote_files',
                'create_quotes', 'update_own_quotes',
                'create_quote_files', 'update_quote_files', 'handle_quote_files',
            ],
            $RWD => [
                'view_own_quotes',
                'create_quotes', 'update_own_quotes', 'delete_own_quotes',
                'view_quote_files',
                'create_quote_files', 'update_quote_files', 'handle_quote_files',
                'delete_quote_files',
            ],
        ],
        'Countries' => [
            $RO => [
                'view_countries',
            ],
            $RW => [
                'view_countries',
                'create_countries', 'update_countries',
            ],
            $RWD => [
                'view_countries',
                'create_countries', 'update_countries', 'delete_countries',
            ],
        ],
        'Contracts' => [
            $RO => [
                'view_own_contracts',
            ],
            $RW => [
                'view_own_contracts',
                'create_contracts', 'update_own_contracts',
            ],
            $RWD => [
                'view_own_contracts',
                'create_contracts', 'update_own_contracts', 'delete_own_contracts',
            ],
        ],
        'Templates' => [
            $RO => [
                'view_templates',
            ],
            $RW => [
                'view_templates',
                'create_templates', 'update_templates',
            ],
            $RWD => [
                'view_templates',
                'create_templates', 'update_templates', 'delete_templates',
            ],
        ],
        'Companies' => [
            $RO => [
                'view_companies',
            ],
            $RW => [
                'view_companies',
                'create_companies', 'update_companies',
            ],
            $RWED => [
                'view_companies',
                'create_companies', 'update_companies',

                'view_companies_where_editor',
                'update_companies_where_editor',
            ],
            $RWD => [
                'view_companies',
                'create_companies', 'update_companies', 'delete_companies',
            ],
        ],
        'Vendors' => [
            $RO => [
                'view_vendors',
            ],
            $RW => [
                'view_vendors',
                'create_vendors', 'update_vendors',
            ],
            $RWD => [
                'view_vendors',
                'create_vendors', 'update_vendors', 'delete_vendors',
            ],
        ],
        'Margins' => [
            $RO => [
                'view_margins',
            ],
            $RW => [
                'view_margins',
                'create_margins', 'update_margins',
            ],
            $RWD => [
                'view_margins',
                'create_margins', 'update_margins', 'delete_margins',
            ],
        ],
        'Discounts' => [
            $RO => [
                'view_discounts',
            ],
            $RW => [
                'view_discounts',
                'create_discounts', 'update_discounts',
            ],
            $RWD => [
                'view_discounts',
                'create_discounts', 'update_discounts', 'delete_discounts',
            ],
        ],
        'Renewals' => [
            $RO => [
                'view_renewals',
            ],
            $RW => [
                'view_renewals',
                'create_renewals', 'update_renewals',
            ],
            $RWD => [
                'view_renewals',
                'create_renewals', 'update_renewals', 'delete_renewals',
            ],
        ],
    ],
];
