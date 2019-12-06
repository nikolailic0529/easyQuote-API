<?php

return [
    '404' => 'No any Quote Templates found',
    'exists_exception' => 'The Template with the same Name already exists.',
    'system_updating_exception' => 'You could not update the system defined Template.',
    'system_deleting_exception' => 'You could not delete the system defined Template.',
    'attached_deleting_exception' => 'You could not delete this Template because it is already in use in one or more Quotes.',
    'designer' => [
        'first_page' => [
            // Quotation For
            ['id' => 'vendor_name', 'label' => 'Vendor Full Name'],
            // For
            ['id' => 'customer_name', 'label' => 'Customer Name'],
            // From
            ['id' => 'company_name', 'label' => 'Internal Company Name'],
            // Quotation Service Level
            ['id' => 'service_level', 'label' => 'Service Level'],
            // Quotation Summary
            ['id' => 'quotation_number', 'label' => 'RFQ Number'],
            ['id' => 'valid_until', 'label' => 'Quotation Closing Date'],
            ['id' => 'support_start', 'label' => 'Support Start Date'],
            ['id' => 'support_end', 'label' => 'Support End Date'],
            ['id' => 'list_price', 'label' => 'Total List Price'],
            ['id' => 'applicable_discounts', 'label' => 'Total Discounts'],
            ['id' => 'final_price', 'label' => 'Final Price'],
            ['id' => 'payment_terms', 'label' => 'Payment Terms'],
            ['id' => 'invoicing_terms', 'label' => 'Invoicing Terms']
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
            ['id' => 'coverage_period_to', 'label' => 'Coverage Period To']
        ],
        'last_page' => [
        ],
        'payment_schedule' => [
            ['id' => 'period', 'label' => 'Payment Schedule Period'],
            ['id' => 'data', 'label' => 'Payment Schedule Data']
        ]
    ]
];
