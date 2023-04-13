<?php

$RO = 'Read Only';
$RW = 'Read & Write';
$RWD = 'Read, Write and Delete';

return [
    [
        'name' => 'Administrator',
        'permissions' => [
                // Settings permissions
                'view_system_settings',
                'update_system_settings',

                // Activity permissions
                'view_activities',

                // Address permissions
                'view_addresses',
                'create_addresses',
                'update_addresses',
                'delete_addresses',

                // Contact permissions
                'view_contacts',
                'create_contacts',
                'update_contacts',
                'delete_contacts',

                // User permissions
                'view_users',
                'invite_collaboration_users',
                'update_users',
                'delete_users',
                'reset_users_password',

                // Role permissions
                'view_roles',
                'create_roles',
                'update_roles',
                'delete_roles',

                // Rescue quote permissions
                'view_quotes',
                'create_quotes',
                'update_quotes',
                'delete_quotes',
                'submit_quotes',
                'download_quote_pdf',
                'download_quote_price',
                'download_quote_schedule',

            // Sales order permissions
            'view_own_sales_orders',
            'create_sales_orders',
            'update_own_sales_orders',
            'delete_own_sales_orders',
            'cancel_sales_orders',
            'resubmit_sales_orders',
            'unravel_sales_orders',
            'alter_active_status_of_sales_orders',
            'download_sales_order_pdf',
            'refresh_status_of_sales_orders',

            // Contract permissions
            'view_contracts',
            'create_contracts',
            'update_contracts',
            'delete_contracts',
            'view_own_quote_contracts',
            'create_quote_contracts',
            'update_own_quote_contracts',
            'delete_own_quote_contracts',
                'download_contract_pdf',

                // Country permissions
                'view_countries',
                'create_countries',
                'update_countries',
                'delete_countries',

                // Company permissions
                'view_companies',
                'create_companies',
                'update_companies',
                'delete_companies',
                'change_companies_ownership',

                // Vendor permissions
                'view_vendors',
                'create_vendors',
                'update_vendors',
                'delete_vendors',

                // Discount permissions
                'view_discounts',
                'create_discounts',
                'update_discounts',
                'delete_discounts',

                // Margin permissions
                'view_margins',
                'create_margins',
                'update_margins',
                'delete_margins',

                // Customer (RFQ) permissions
                'delete_customers',

                // Asset permissions
                'view_assets',
                'create_assets',
                'update_assets',
                'delete_assets',
                'change_assets_ownership',

                // Rescue quote permissions
                'view_own_external_quotes',
                'create_external_quotes',
                'update_own_external_quotes',
                'delete_own_external_quotes',
                'view_own_internal_quotes',
                'create_internal_quotes',
                'update_own_internal_quotes',
                'delete_own_internal_quotes',
                'view_quote_files',
                'create_quote_files',
                'update_quote_files',
                'handle_quote_files',
                'delete_quote_files',

                // Generic template permissions
                'view_templates',
                'create_templates',
                'update_templates',
                'delete_templates',

                // HPE Contract permissions
                'view_own_hpe_contracts',
                'create_hpe_contracts',
                'update_own_hpe_contracts',
                'delete_own_hpe_contracts',
                'download_hpe_contract_pdf',

                // Quote template permissions
                'view_own_quote_templates',
                'create_quote_templates',
                'update_own_quote_templates',
                'delete_own_quote_templates',

                // Contract template permissions
                'view_own_contract_templates',
                'create_contract_templates',
                'update_own_contract_templates',
                'delete_own_contract_templates',

                // HPE contract template permissions
                'view_own_hpe_contract_templates',
                'create_hpe_contract_templates',
                'update_own_hpe_contract_templates',
                'delete_own_hpe_contract_templates',

                // Quote task template permissions
                'view_quote_task_template',
                'update_quote_task_template',

                // Opportunity form template permissions
                'view_opportunity_form_template',
                'update_opportunity_form_template',

                // Worldwide quote permissions
                'view_own_ww_quotes',
                'create_ww_quotes',
                'update_own_ww_quotes',
                'delete_own_ww_quotes',
                'download_ww_quote_pdf',
                'download_ww_quote_distributor_file',
                'download_ww_quote_payment_schedule',
                'view_own_ww_quote_files',
                'create_ww_quote_files',
                'update_own_ww_quote_files',
                'handle_own_ww_quote_files',
                'delete_own_ww_quote_files',
                'change_ww_quotes_ownership',

                // Opportunity permissions
                'view_opportunities',
                'create_opportunities',
                'update_own_opportunities',
                'delete_own_opportunities',
                'change_opportunities_ownership',

                // Team permissions
                'view_teams',
                'create_teams',
                'update_teams',
                'delete_teams',
            ],
        'privileges' => [
                'Addresses' => $RWD,
                'Contacts' => $RWD,
                'Assets' => $RWD,
                'Settings' => $RW,
                'Countries' => $RWD,
                'Audit' => $RO,
            'Users' => $RWD,
            'Quotes' => $RWD,
            'Contracts' => $RWD,
            'Templates' => $RWD,
            'Companies' => $RWD,
            'Vendors' => $RWD,
            'Margins' => $RWD,
            'Discounts' => $RWD,
            'Teams' => $RWD,
        ],
        'access' => [
            'access_contact_direction' => 'all',
            'access_company_direction' => 'all',
            'access_opportunity_direction' => 'all',
            'access_opportunity_pipeline_direction' => 'all',
            'access_worldwide_quote_direction' => 'all',
            'access_sales_order_direction' => 'all',
        ],
    ],
    [
        'name' => 'Sales Manager',
        'permissions' => [
                // Rescue quote permissions
                'view_own_quotes',
                'create_quotes',
                'update_own_quotes',
                'delete_own_quotes',
                'submit_own_quotes',
                'download_quote_pdf',
                'download_quote_price',
                'download_quote_schedule',
                'view_quote_files',
                'create_quote_files',
                'update_quote_files',
                'handle_quote_files',
                'delete_quote_files',

                // Contract permissions
                'view_own_contracts',
                'create_contracts',
                'update_own_contracts',
                'delete_own_contracts',
                'download_contract_pdf',

                // HPE Contract permissions
                'download_hpe_contract_pdf',

                // Generic template permissions
                'view_templates',
                'create_templates',
                'update_templates',
                'delete_templates',

                // Company permissions
                'view_companies',
                'create_companies',
                'update_companies',
                'delete_companies',

                // Vendor permissions
                'view_vendors',
                'create_vendors',
                'update_vendors',
                'delete_vendors',

                // Discount permissions
                'view_discounts',
                'create_discounts',
                'update_discounts',
                'delete_discounts',

                // Margin permissions
                'view_margins',
                'create_margins',
                'update_margins',
                'delete_margins',

                // Asset permissions
                'view_assets',
                'create_assets',
                'update_assets',

                // Address permissions
                'view_addresses',
                'create_addresses',
                'update_addresses',

                // Contact permissions
                'view_contacts',
                'create_contacts',
                'update_contacts',
            ],
        'privileges' => [
                'Addresses' => $RW,
                'Contacts' => $RW,
                'Assets' => $RW,
                'Quotes' => $RWD,
                'Contracts' => $RWD,
                'Templates' => $RWD,
                'Companies' => $RWD,
                'Vendors' => $RWD,
                'Margins' => $RWD,
                'Discounts' => $RWD,
            ],
    ],
];
