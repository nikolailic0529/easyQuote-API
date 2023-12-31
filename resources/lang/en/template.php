<?php

return [
    'designer' => [
        'first_page' => [
            // Quotation For
            ['id' => 'vendor_name', 'label' => 'Vendor Full Name'],
            // For
            ['id' => 'customer_name', 'label' => 'Customer Name'],
            // From
            ['id' => 'company_name', 'label' => 'Internal Company Name'],
            // Quotation Service Level
            ['id' => 'service_levels', 'label' => 'Service Level (s)'],
            // Quotation Summary
            ['id' => 'quotation_number', 'label' => 'RFQ Number'],
            ['id' => 'valid_until', 'label' => 'Quotation Closing Date'],
            ['id' => 'support_start', 'label' => 'Support Start Date'],
            ['id' => 'support_end', 'label' => 'Support End Date'],
            ['id' => 'list_price', 'label' => 'Total List Price'],
            ['id' => 'applicable_discounts', 'label' => 'Total Discounts'],
            ['id' => 'final_price', 'label' => 'Final Price'],
            ['id' => 'invoicing_terms', 'label' => 'Invoicing Terms'],
            ['id' => 'service_agreement_id', 'label' => 'Service Agreement Id'],
            ['id' => 'system_handle', 'label' => 'System Handle'],
        ],
        'data_pages' => [
            ['id' => 'pricing_document', 'label' => 'Pricing Document'],
            ['id' => 'service_agreement_id', 'label' => 'Service Agreement Id'],
            ['id' => 'system_handle', 'label' => 'System Handle'],
            ['id' => 'equipment_address', 'label' => 'Equipment Address'],
            ['id' => 'hardware_contact', 'label' => 'Hardware Contact'],
            ['id' => 'hardware_phone', 'label' => 'Hardware Phone'],
            ['id' => 'software_address', 'label' => 'Software Address'],
            ['id' => 'software_contact', 'label' => 'Software Contact'],
            ['id' => 'software_phone', 'label' => 'Software Phone'],
            ['id' => 'coverage_period_from', 'label' => 'Coverage Period From'],
            ['id' => 'coverage_period_to', 'label' => 'Coverage Period To'],
            // Quotation Service Level
            ['id' => 'service_levels', 'label' => 'Service Level (s)'],
        ],
        'last_page' => [],
        'payment_schedule' => [
            ['id' => 'period', 'label' => 'Payment Schedule Period'],
            ['id' => 'data', 'label' => 'Payment Schedule Data'],
        ],
    ],
    'quote_data_headers' => [
        'product_no' => [
            'value' => 'Product No',
            'label' => 'Product No',
        ],
        'service_sku' => [
            'value' => 'Service SKU',
            'label' => 'Service SKU',
        ],
        'description' => [
            'value' => 'Description',
            'label' => 'Description',
        ],
        'serial_no' => [
            'value' => 'Serial Number',
            'label' => 'Serial Number',
        ],
        'date_from' => [
            'value' => 'From Date',
            'label' => 'From Date',
        ],
        'date_to' => [
            'value' => 'To Date',
            'label' => 'To Date',
        ],
        'qty' => [
            'value' => 'Quantity',
            'label' => 'Quantity',
        ],
        'price' => [
            'value' => 'Price',
            'label' => 'Price',
        ],
        'vendor_name' => [
            'value' => 'Manufacturer',
            'label' => 'Manufacturer',
        ],
        'country_name' => [
            'value' => 'Country',
            'label' => 'Country',
        ],
        'duration' => [
            'value' => 'Duration',
            'label' => 'Duration',
        ],
        'total' => [
            'value' => 'Total',
            'label' => 'Total',
        ],
        'total_price' => [
            'value' => 'Total',
            'label' => 'Total Price',
        ],
        'sub_total' => [
            'value' => 'Sub Total',
            'label' => 'Sub Total',
        ],
        'total_including_tax' => [
            'value' => 'Total (inc. tax)',
            'label' => 'Total including tax',
        ],
        'grand_total' => [
            'value' => 'Grand Total',
            'label' => 'Grand Total',
        ],
        'vendor_short_code' => [
            'value' => 'Vendor',
            'label' => 'Vendor Name',
        ],
        'machine_address_string' => [
            'value' => 'Machine Address',
            'label' => 'Machine Address',
        ],
        'service_level' => [
            'value' => 'SLA',
            'label' => 'Service Level',
        ],
        'service_level_description' => [
            'value' => 'Service Level',
            'label' => 'Service Level',
        ],
        'auto_generated_serial_number_text' => [
            'value' => "Serial numbers are user's generated",
            'label' => 'Auto-generated Serial Number Text',
        ],
        'contract_duration' => [
            'value' => 'Support Duration',
            'label' => 'Support Duration',
        ],
        'support_dates_assumed' => [
            'value' => 'The dates are based on assumption.',
            'label' => 'Support Dates Assumed',
        ],
        'canada_tax_text' => [
            'value' => 'Please note that tax will apply in Canada depending on the state. Therefore it is really important that the information stated above is reflecting the actual installation details since tax may vary on location.',
            'label' => 'Canada Tax Information',
        ],
        'ca_ct_hi_md_ma_nv_pr_ri_tn_states_tax_text' => [
            'value' => 'Please note that tax will apply if any of the machines above are located in California, Connecticut, Hawaii, Maryland, Massachusetts, Nevada, Puerto Rico, Rhode Island or Tennessee',
            'label' => 'Specific States (CA, CT, HI, MD, MA, NV, PR, RI, TN) Tax Information',
        ],
    ],
    'contract_data_headers' => [
        'product_no' => [
            'value' => 'Product No',
            'label' => 'Product No',
        ],
        'description' => [
            'value' => 'Description',
            'label' => 'Description',
        ],
        'serial_no' => [
            'value' => 'Serial Number',
            'label' => 'Serial Number',
        ],
        'date_from' => [
            'value' => 'From Date',
            'label' => 'From Date',
        ],
        'date_to' => [
            'value' => 'To Date',
            'label' => 'To Date',
        ],
        'qty' => [
            'value' => 'Quantity',
            'label' => 'Quantity',
        ],
    ],
    'sales_order_data_headers' => [
        'product_no' => [
            'value' => 'Product No',
            'label' => 'Product No',
        ],
        'description' => [
            'value' => 'Description',
            'label' => 'Description',
        ],
        'serial_no' => [
            'value' => 'Serial Number',
            'label' => 'Serial Number',
        ],
        'date_from' => [
            'value' => 'From Date',
            'label' => 'From Date',
        ],
        'date_to' => [
            'value' => 'To Date',
            'label' => 'To Date',
        ],
        'qty' => [
            'value' => 'Quantity',
            'label' => 'Quantity',
        ],
        'auto_generated_serial_number_text' => [
            'value' => "Serial numbers are user's generated",
            'label' => 'Auto-generated Serial Number Text',
        ],
        'contract_duration' => [
            'value' => 'Support Duration',
            'label' => 'Support Duration',
        ],
        'canada_tax_text' => [
            'value' => 'Please note that tax will apply in Canada depending on the state. Therefore it is really important that the information stated above is reflecting the actual installation details since tax may vary on location.',
            'label' => 'Canada Tax Information',
        ],
        'ca_ct_hi_md_ma_nv_pr_ri_tn_states_tax_text' => [
            'value' => 'Please note that tax will apply if any of the machines above are located in California, Connecticut, Hawaii, Maryland, Massachusetts, Nevada, Puerto Rico, Rhode Island or Tennessee',
            'label' => 'Specific States (CA, CT, HI, MD, MA, NV, PR, RI, TN) Tax Information',
        ],
    ],
    'hpe_contract_data_headers' => [
        'hpe_contract' => [
            'value' => 'HPE Contract',
            'label' => 'HPE Contract No.',
        ],
        'support_account_reference' => [
            'value' => 'Support Account Reference',
            'label' => 'Support Account Reference',
        ],
        'serial_number_details' => [
            'value' => 'Serial Number Details',
            'label' => 'Serial Number Details',
        ],
        'hpe_sales_order' => [
            'value' => 'HPE Sales Order',
            'label' => 'Hpe Sales Order No.',
        ],
        'number' => [
            'value' => 'No.',
            'label' => 'No.',
        ],
        'asset_location_details' => [
            'value' => 'Asset Location Details',
            'label' => 'Asset Location Details',
        ],
        'authorization_date' => [
            'value' => 'Purchase Order / Authorization Date',
            'label' => 'Authorization Date',
        ],
        'authorization' => [
            'value' => 'Purchase Order / Authorization',
            'label' => 'Authorization',
        ],
        'amp_id' => [
            'value' => 'AMP ID',
            'label' => 'AMP ID',
        ],
        'contract_no' => [
            'value' => 'HPE Contract',
            'label' => 'Contract No',
        ],
        'date_from' => [
            'value' => 'Start Date',
            'label' => 'Start Date',
        ],
        'date_to' => [
            'value' => 'End Date',
            'label' => 'End Date',
        ],
        'sales_order' => [
            'value' => 'HPE Sales Order',
            'label' => 'Sales Order',
        ],
        'product_no' => [
            'value' => 'Product',
            'label' => 'Product',
        ],
        'description' => [
            'value' => 'Description',
            'label' => 'Description',
        ],
        'qty' => [
            'value' => 'Quantity',
            'label' => 'Quantity',
        ],
        'serial_no' => [
            'value' => 'Serial No',
            'label' => 'Serial No',
        ],
        'support_account' => [
            'value' => 'Support Account Reference',
            'label' => 'Support Account Reference',
        ],
        'contract_summary' => [
            'value' => 'Contract Summary',
            'label' => 'Contract Summary',
        ],
        'hpe_contacts' => [
            'value' => 'HPE Contacts',
            'label' => 'HPE Contacts',
        ],
        'sold_to_address' => [
            'value' => 'Sold To Address',
            'label' => 'Sold Address',
        ],
        'bill_to_address' => [
            'value' => 'Bill To Address',
            'label' => 'Bill To Address',
        ],
        'address' => [
            'value' => 'Address',
            'label' => 'Address',
        ],
        'contract_admin' => [
            'value' => 'Contract Admin',
            'label' => 'Contract Admin',
        ],
        'service_overview' => [
            'value' => 'Service Overview',
            'label' => 'Service Overview',
        ],
        'service_level' => [
            'value' => 'Service Level',
            'label' => 'Service Level',
        ],
        'contract_details' => [
            'value' => 'Contract Details',
            'label' => 'Contract Details',
        ],
        'customer_contacts' => [
            'value' => 'Customer Contacts',
            'label' => 'Customer Contacts',
        ],
        'support_service_details' => [
            'value' => 'Support Service Details',
            'label' => 'Support Service Details',
        ],
        'support_account_reference_detail' => [
            'value' => 'Support Account Reference Detail',
            'label' => 'Support Account Reference Detail',
        ],
        'page_no' => [
            'value' => 'Page',
            'label' => 'Page No.',
        ],
    ],
];
