<?php

namespace App\Models\QuoteTemplate;

final class TemplateDesign
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
        'hpe_contract' => [
            'first_page' => [
                ['id' => 'contract_date', 'label' => 'Contract Date'],
                ['id' => 'purchase_order_date', 'label' => 'Purchase Order [Authorization Date]'],
                ['id' => 'purchase_order_no', 'label' => 'Purchase Order [Authorization]'],
                ['id' => 'amp_id', 'label' => 'AMP ID'],
                ['id' => 'contract_details', 'label' => 'Contract Details'],
            ],
            'contract_summary' => [
                ['id' => 'contract_date', 'label' => 'Contract Date'],
                ['id' => 'amp_id', 'label' => 'AMP ID'],
                ['id' => 'customer_sold_address', 'label' => 'Sold Address'],
                ['id' => 'customer_bill_address', 'label' => 'Bill Address'],
                ['id' => 'hpe_address', 'label' => 'HPE Address'],
                ['id' => 'contract_admin', 'label' => 'Contract Admin'],
                ['id' => 'service_overview', 'label' => 'Service Overview'],
            ],
            'contract_details' => [
                ['id' => 'contract_date', 'label' => 'Contract Date'],
                ['id' => 'contract_start_date', 'label' => 'Contract Start Date'],
                ['id' => 'contract_end_date', 'label' => 'Contract End Date'],
                ['id' => 'purchase_order_date', 'label' => 'Purchase Order [Authorization Date]'],
                ['id' => 'purchase_order_no', 'label' => 'Purchase Order [Authorization]'],
                ['id' => 'hpe_sales_order', 'label' => 'HPE Sales Order'],

                ['id' => 'hw_delivery_contact', 'label' => 'HW Delivery Contact'],
                ['id' => 'sw_delivery_contact', 'label' => 'SW Delivery Contact'],
                ['id' => 'primary_support_recipient', 'label' => 'Primary Support Recipient'],
                ['id' => 'entitled_party', 'label' => 'Entitled Party'],
                ['id' => 'end_customer_name', 'label' => 'End Customer Name'],
            ],
            'support_service_details' => [
                ['id' => 'contract_date', 'label' => 'Contract Date'],
                ['id' => 'support_service_levels', 'label' => 'Support Service Levels'],
            ],
            'support_account_reference_detail' => [
                ['id' => 'contract_date', 'label' => 'Contract Date'],
                ['id' => 'support_account_reference', 'label' => 'Support Account Reference [Table]'],
            ],
            'asset_location_details' => [
                ['id' => 'contract_date', 'label' => 'Contract Date'],
                ['id' => 'asset_locations', 'label' => 'Asset Locations [Table]'],
            ],
            'serial_number_details' => [
                ['id' => 'contract_date', 'label' => 'Contract Date'],
                ['id' => 'serial_numbers', 'label' => 'Serial Numbers [Table]'],
            ]
        ]
    ];

    public const IMAGE_FLAG = 'is_image';

    public static function getPages(string $name): array
    {
        if (isset(static::$designCache[$name])) {
            return static::$designCache[$name];
        }

        $pages = [];

        foreach (static::DESIGNS[$name] ?? [] as $key => $tags) {
            $pages[$key] = collect($tags)->map(fn ($tag) => $tag + [static::IMAGE_FLAG => false])->toArray();
        }

        return static::$designCache[$name] = $pages;
    }
}
