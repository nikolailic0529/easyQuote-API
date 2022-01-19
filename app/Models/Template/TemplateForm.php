<?php

namespace App\Models\Template;

final class TemplateForm
{
    protected static array $designCache = [];

    public const QUOTE = 'quote';

    public const HPE_CONTRACT = 'hpe_contract';

    public const DESIGNS = [
        'quote' => [
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
                ['id' => 'data', 'label' => 'Payment Schedule Data']
            ]
        ],
        'ww_quote' => [
            'first_page' => [
                ['id' => 'quote_data_aggregation', 'label' => 'Quote Data Aggregation'],
                ['id' => 'company_name', 'label' => 'Internal Company Name'],
                ['id' => 'service_levels', 'label' => 'Service Level (s)'],
                ['id' => 'quotation_number', 'label' => 'RFQ Number'],
                ['id' => 'valid_until', 'label' => 'Quotation Closing Date'],
                ['id' => 'support_start', 'label' => 'Support Start Date'],
                ['id' => 'support_end', 'label' => 'Support End Date'],
                ['id' => 'contract_duration', 'label' => 'Contract Duration'],
                ['id' => 'list_price', 'label' => 'Total List Price'],
                ['id' => 'applicable_discounts', 'label' => 'Total Discounts'],
                ['id' => 'final_price', 'label' => 'Final Price'],
                ['id' => 'invoicing_terms', 'label' => 'Invoicing Terms'],
                ['id' => 'system_handle', 'label' => 'System Handle'],

                ['id' => 'logo_set_x1', 'label' => 'Logo Set X1'],
                ['id' => 'logo_set_x2', 'label' => 'Logo Set X2'],
                ['id' => 'logo_set_x3', 'label' => 'Logo Set X3'],

                ['id' => 'purchase_order_number', 'label' => 'Purchase Order Number'],
                ['id' => 'contract_number', 'label' => 'Contract Number'],
                ['id' => 'vat_number', 'label' => 'VAT Number'],
                ['id' => 'payment_terms', 'label' => 'Payment Terms'],
                ['id' => 'support_start_assumed_char', 'label' => 'Support Start Assumed (*)'],
                ['id' => 'support_end_assumed_char', 'label' => 'Support End Assumed (*)'],
                ['id' => 'footer_notes', 'label' => 'Footer notes'],

                # Reseller
                ['id' => 'customer_name', 'label' => 'Company/Reseller Name'],

                ['id' => 'contact_country', 'label' => 'Company/Reseller Country'],
                ['id' => 'contact_name', 'label' => 'Company/Reseller Contact Name'],
                ['id' => 'contact_email', 'label' => 'Company/Reseller Contact Email'],
                ['id' => 'contact_phone', 'label' => 'Company/Reseller Contact Phone'],

                # End User
                ['id' => 'end_user_name', 'label' => 'End Customer Name'],

                ['id' => 'end_user_contact_country', 'label' => 'End Customer Contact Country'],
                ['id' => 'end_user_contact_name', 'label' => 'End Customer Contact Name'],
                ['id' => 'end_user_contact_email', 'label' => 'End Customer Contact Email'],

                # Account Manager
                ['id' => 'account_manager_name', 'label' => 'Account Manager Name'],
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
                ['id' => 'coverage_period', 'label' => 'Coverage Period'],
                ['id' => 'coverage_period_from', 'label' => 'Coverage Period From'],
                ['id' => 'coverage_period_to', 'label' => 'Coverage Period To'],
                ['id' => 'support_start_assumed_char', 'label' => 'Support Start Assumed (*)'],
                ['id' => 'support_end_assumed_char', 'label' => 'Support End Assumed (*)'],
                // Quotation Service Level
                ['id' => 'service_levels', 'label' => 'Service Level (s)'],

                ['id' => 'purchase_order_number', 'label' => 'Purchase Order Number'],
                ['id' => 'vat_number', 'label' => 'VAT Number'],

                ['id' => 'logo_set_x1', 'label' => 'Logo Set X1'],
                ['id' => 'logo_set_x2', 'label' => 'Logo Set X2'],
                ['id' => 'logo_set_x3', 'label' => 'Logo Set X3'],
            ],
            'last_page' => [
                ['id' => 'logo_set_x1', 'label' => 'Logo Set X1'],
                ['id' => 'logo_set_x2', 'label' => 'Logo Set X2'],
                ['id' => 'logo_set_x3', 'label' => 'Logo Set X3']
            ],
            'payment_schedule' => [
                ['id' => 'period', 'label' => 'Payment Schedule Period'],
                ['id' => 'data', 'label' => 'Payment Schedule Data'],

                ['id' => 'logo_set_x1', 'label' => 'Logo Set X1'],
                ['id' => 'logo_set_x2', 'label' => 'Logo Set X2'],
                ['id' => 'logo_set_x3', 'label' => 'Logo Set X3'],
            ]
        ],
        'hpe_contract' => [
            'first_page' => [
                ['id' => 'contract_number', 'label' => 'Contract Number'], // *
                ['id' => 'contract_date', 'label' => 'Contract Date'], // *
                ['id' => 'purchase_order_date', 'label' => 'Purchase Order [Authorization Date]'], // *
                ['id' => 'purchase_order_no', 'label' => 'Purchase Order [Authorization]'], // *
                ['id' => 'amp_id', 'label' => 'AMP ID'], // *
                ['id' => 'contract_details', 'label' => 'Contract Details [Table]'], // *
            ],
            'contract_summary' => [
                ['id' => 'contract_number', 'label' => 'Contract Number'], // *

                ['id' => 'contract_date', 'label' => 'Contract Date'], // *

                ['id' => 'amp_id', 'label' => 'AMP ID'], // *

                ['id' => 'sold_contact.org_name', 'label' => 'Sold To Address [Org. Name]'],
                ['id' => 'sold_contact.attn', 'label' => 'Sold To Address [Attn]'],
                ['id' => 'sold_contact.address', 'label' => 'Sold To Address [Address]'],
                ['id' => 'sold_contact.post_code', 'label' => 'Sold To Address [Post Code]'],
                ['id' => 'sold_contact.country', 'label' => 'Sold To Address [Country]'],
                ['id' => 'sold_contact.city', 'label' => 'Sold To Address [City]'],
                ['id' => 'sold_contact.phone', 'label' => 'Sold To Address [Phone]'],
                ['id' => 'sold_contact.email', 'label' => 'Sold To Address [Email]'],

                ['id' => 'bill_contact.org_name', 'label' => 'Bill To Address [Org. Name]'],
                ['id' => 'bill_contact.attn', 'label' => 'Bill To Address [Attn]'],
                ['id' => 'bill_contact.address', 'label' => 'Bill To Address [Address]'],
                ['id' => 'bill_contact.post_code', 'label' => 'Bill To Address [Post Code]'],
                ['id' => 'bill_contact.country', 'label' => 'Bill To Address [Country]'],
                ['id' => 'bill_contact.city', 'label' => 'Bill To Address [City]'],
                ['id' => 'bill_contact.phone', 'label' => 'Bill To Address [Phone]'],
                ['id' => 'bill_contact.email', 'label' => 'Bill To Address [Email]'],

                ['id' => 'service_overview', 'label' => 'Service Overview [Table]'], // *
            ],
            'contract_details' => [
                ['id' => 'contract_assets', 'label' => 'Contract Assets [Table]'], // *

                ['id' => 'contract_date', 'label' => 'Contract Date'], // *

                ['id' => 'purchase_order_date', 'label' => 'Purchase Order [Authorization Date]'],  // create input *
                ['id' => 'purchase_order_no', 'label' => 'Purchase Order [Authorization]'], // create input *
                ['id' => 'hpe_sales_order_no', 'label' => 'HPE Sales Order'], // create input *

                ['id' => 'sold_contact.org_name', 'label' => 'Sold To Address [Org. Name]'],
                ['id' => 'sold_contact.attn', 'label' => 'Sold To Address [Attn]'],
                ['id' => 'sold_contact.address', 'label' => 'Sold To Address [Address]'],
                ['id' => 'sold_contact.post_code', 'label' => 'Sold To Address [Post Code]'],
                ['id' => 'sold_contact.country', 'label' => 'Sold To Address [Country]'],
                ['id' => 'sold_contact.city', 'label' => 'Sold To Address [City]'],
                ['id' => 'sold_contact.phone', 'label' => 'Sold To Address [Phone]'],
                ['id' => 'sold_contact.email', 'label' => 'Sold To Address [Email]'],

                ['id' => 'bill_contact.org_name', 'label' => 'Bill To Address [Org. Name]'],
                ['id' => 'bill_contact.attn', 'label' => 'Bill To Address [Attn]'],
                ['id' => 'bill_contact.address', 'label' => 'Bill To Address [Address]'],
                ['id' => 'bill_contact.post_code', 'label' => 'Bill To Address [Post Code]'],
                ['id' => 'bill_contact.country', 'label' => 'Bill To Address [Country]'],
                ['id' => 'bill_contact.city', 'label' => 'Bill To Address [City]'],
                ['id' => 'bill_contact.phone', 'label' => 'Bill To Address [Phone]'],
                ['id' => 'bill_contact.email', 'label' => 'Bill To Address [Email]'],

                /** Contacts */
                ['id' => 'pr_support_contact.attn', 'label' => 'Primary Support Recipient [Attn]'], // *
                ['id' => 'pr_support_contact.email', 'label' => 'Primary Support Recipient [Email]'], // *
                ['id' => 'pr_support_contact.phone', 'label' => 'Primary Support Recipient [Phone]'], // *

                ['id' => 'hw_delivery_contact.attn', 'label' => 'HW Delivery Contact [Attn]'], // *
                ['id' => 'hw_delivery_contact.email', 'label' => 'HW Delivery Contact [Email]'], // *
                ['id' => 'hw_delivery_contact.phone', 'label' => 'HW Delivery Contact [Phone]'], // *

                ['id' => 'sw_delivery_contact.attn', 'label' => 'SW Delivery Contact [Attn]'], // *
                ['id' => 'sw_delivery_contact.email', 'label' => 'SW Delivery Contact [Email]'], // *
                ['id' => 'sw_delivery_contact.phone', 'label' => 'SW Delivery Contact [Phone]'], // *

                ['id' => 'entitled_party_contact.org_name', 'label' => 'Entitled Party Contact [Org. Name]'], // *
                ['id' => 'entitled_party_contact.attn', 'label' => 'Entitled Party Contact [Attn]'], // *
                ['id' => 'entitled_party_contact.address', 'label' => 'Entitled Party Contact [Address]'], // *
                ['id' => 'entitled_party_contact.post_code', 'label' => 'Entitled Party Contact [Post Code]'], // *
                ['id' => 'entitled_party_contact.country', 'label' => 'Entitled Party Contact [Country]'], // *
                ['id' => 'entitled_party_contact.city', 'label' => 'Entitled Party Contact [City]'], // *

                ['id' => 'end_customer_contact.org_name', 'label' => 'End Customer Contact [Org. Name]'], // *
                ['id' => 'end_customer_contact.attn', 'label' => 'End Customer Contact [Attn]'], // *
                ['id' => 'end_customer_contact.address', 'label' => 'End Customer Contact [Address]'], // *
                ['id' => 'end_customer_contact.post_code', 'label' => 'End Customer Contact [Post Code]'], // *
                ['id' => 'end_customer_contact.country', 'label' => 'End Customer Contact [Country]'], // *
                ['id' => 'end_customer_contact.city', 'label' => 'End Customer Contact [City]'], // *
            ],
            'contract_page' => [
                ['id' => 'contract_date', 'label' => 'Contract Date'], // *

                ['id' => 'purchase_order_date', 'label' => 'Purchase Order [Authorization Date]'],  // create input *
                ['id' => 'purchase_order_no', 'label' => 'Purchase Order [Authorization]'], // create input *
                ['id' => 'hpe_sales_order_no', 'label' => 'HPE Sales Order'], // create input *

                ['id' => 'sold_contact.org_name', 'label' => 'Sold To Address [Org. Name]'],
                ['id' => 'sold_contact.attn', 'label' => 'Sold To Address [Attn]'],
                ['id' => 'sold_contact.address', 'label' => 'Sold To Address [Address]'],
                ['id' => 'sold_contact.post_code', 'label' => 'Sold To Address [Post Code]'],
                ['id' => 'sold_contact.country', 'label' => 'Sold To Address [Country]'],
                ['id' => 'sold_contact.city', 'label' => 'Sold To Address [City]'],
                ['id' => 'sold_contact.phone', 'label' => 'Sold To Address [Phone]'],
                ['id' => 'sold_contact.email', 'label' => 'Sold To Address [Email]'],

                ['id' => 'bill_contact.org_name', 'label' => 'Bill To Address [Org. Name]'],
                ['id' => 'bill_contact.attn', 'label' => 'Bill To Address [Attn]'],
                ['id' => 'bill_contact.address', 'label' => 'Bill To Address [Address]'],
                ['id' => 'bill_contact.post_code', 'label' => 'Bill To Address [Post Code]'],
                ['id' => 'bill_contact.country', 'label' => 'Bill To Address [Country]'],
                ['id' => 'bill_contact.city', 'label' => 'Bill To Address [City]'],
                ['id' => 'bill_contact.phone', 'label' => 'Bill To Address [Phone]'],
                ['id' => 'bill_contact.email', 'label' => 'Bill To Address [Email]'],

                /** Contacts */
                ['id' => 'pr_support_contact.attn', 'label' => 'Primary Support Recipient [Attn]'], // *
                ['id' => 'pr_support_contact.email', 'label' => 'Primary Support Recipient [Email]'], // *
                ['id' => 'pr_support_contact.phone', 'label' => 'Primary Support Recipient [Phone]'], // *

                ['id' => 'hw_delivery_contact.attn', 'label' => 'HW Delivery Contact [Attn]'], // *
                ['id' => 'hw_delivery_contact.email', 'label' => 'HW Delivery Contact [Email]'], // *
                ['id' => 'hw_delivery_contact.phone', 'label' => 'HW Delivery Contact [Phone]'], // *

                ['id' => 'sw_delivery_contact.attn', 'label' => 'SW Delivery Contact [Attn]'], // *
                ['id' => 'sw_delivery_contact.email', 'label' => 'SW Delivery Contact [Email]'], // *
                ['id' => 'sw_delivery_contact.phone', 'label' => 'SW Delivery Contact [Phone]'], // *

                ['id' => 'entitled_party_contact.org_name', 'label' => 'Entitled Party Contact [Org. Name]'], // *
                ['id' => 'entitled_party_contact.attn', 'label' => 'Entitled Party Contact [Attn]'], // *
                ['id' => 'entitled_party_contact.address', 'label' => 'Entitled Party Contact [Address]'], // *
                ['id' => 'entitled_party_contact.post_code', 'label' => 'Entitled Party Contact [Post Code]'], // *
                ['id' => 'entitled_party_contact.country', 'label' => 'Entitled Party Contact [Country]'], // *
                ['id' => 'entitled_party_contact.city', 'label' => 'Entitled Party Contact [City]'], // *

                ['id' => 'end_customer_contact.org_name', 'label' => 'End Customer Contact [Org. Name]'], // *
                ['id' => 'end_customer_contact.attn', 'label' => 'End Customer Contact [Attn]'], // *
                ['id' => 'end_customer_contact.address', 'label' => 'End Customer Contact [Address]'], // *
                ['id' => 'end_customer_contact.post_code', 'label' => 'End Customer Contact [Post Code]'], // *
                ['id' => 'end_customer_contact.country', 'label' => 'End Customer Contact [Country]'], // *
                ['id' => 'end_customer_contact.city', 'label' => 'End Customer Contact [City]'], // *
            ],
            'support_service_details' => [
                ['id' => 'contract_number', 'label' => 'Contract Number'],
                ['id' => 'contract_date', 'label' => 'Contract Date'],
                ['id' => 'support_services', 'label' => 'Support Services [List]'],
            ],
            'support_account_reference_detail' => [
                ['id' => 'end_customer_contact.org_name', 'label' => 'End Customer Contact [Org. Name]'], // *
                ['id' => 'end_customer_contact.attn', 'label' => 'End Customer Contact [Attn]'], // *
                ['id' => 'end_customer_contact.address', 'label' => 'End Customer Contact [Address]'], // *
                ['id' => 'end_customer_contact.post_code', 'label' => 'End Customer Contact [Post Code]'], // *
                ['id' => 'end_customer_contact.country', 'label' => 'End Customer Contact [Country]'], // *
                ['id' => 'end_customer_contact.city', 'label' => 'End Customer Contact [City]'], // *

                ['id' => 'contract_number', 'label' => 'Contract Number'],
                ['id' => 'contract_date', 'label' => 'Contract Date'],
                ['id' => 'support_account_reference', 'label' => 'Support Account Reference [Table]'],
            ],
            'asset_location_details' => [
                ['id' => 'end_customer_contact.org_name', 'label' => 'End Customer Contact [Org. Name]'], // *
                ['id' => 'end_customer_contact.attn', 'label' => 'End Customer Contact [Attn]'], // *
                ['id' => 'end_customer_contact.address', 'label' => 'End Customer Contact [Address]'], // *
                ['id' => 'end_customer_contact.post_code', 'label' => 'End Customer Contact [Post Code]'], // *
                ['id' => 'end_customer_contact.country', 'label' => 'End Customer Contact [Country]'], // *
                ['id' => 'end_customer_contact.city', 'label' => 'End Customer Contact [City]'], // *

                ['id' => 'contract_number', 'label' => 'Contract Number'],
                ['id' => 'contract_date', 'label' => 'Contract Date'],
                ['id' => 'asset_locations', 'label' => 'Asset Locations [Table]'],
            ],
            'serial_number_details' => [
                ['id' => 'contract_number', 'label' => 'Contract Number'],
                ['id' => 'contract_date', 'label' => 'Contract Date'],
                ['id' => 'serial_numbers', 'label' => 'Serial Numbers [Table]'],
            ],

        ]
    ];

    public const IMAGE_FLAG = 'is_image';

    public static function getPages(string $name): array
    {
        if (isset(TemplateForm::$designCache[$name])) {
            return TemplateForm::$designCache[$name];
        }

        $pages = [];

        foreach (TemplateForm::DESIGNS[$name] ?? [] as $key => $tags) {
            $pages[$key] = collect($tags)->map(fn ($tag) => $tag + [TemplateForm::IMAGE_FLAG => false])->toArray();
        }

        return TemplateForm::$designCache[$name] = $pages;
    }

    public static function parseTemplateDesign(string $design, array $attributes): array
    {
        $design = preg_replace_callback('/{{(.*)}}/m', fn ($item) => data_get($attributes, last($item)), $design);

        return json_decode($design, true);
    }
}
