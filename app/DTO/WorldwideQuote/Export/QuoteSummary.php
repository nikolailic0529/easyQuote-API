<?php

namespace App\DTO\WorldwideQuote\Export;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class QuoteSummary extends DataTransferObject
{
    protected array $exceptKeys = ['export_file_name'];

    /**
     * @Constraints\NotBlank
     */
    public string $company_name;

    /**
     * @Constraints\NotBlank
     */
    public string $quotation_number;

    public string $export_file_name;

    /**
     * @Constraints\NotBlank
     */
    public string $customer_name;

    public string $service_levels;

    public string $invoicing_terms;

    public string $payment_terms;

    /**
     * @Constraints\NotBlank
     */
    public string $support_start;

    public string $support_start_assumed_char;

    /**
     * @Constraints\NotBlank
     */
    public string $support_end;

    public string $support_end_assumed_char;

    /**
     * @Constraints\NotBlank
     */
    public string $valid_until;

    public string $contact_name;

    public string $contact_email;

    public string $contact_phone;

    /**
     * @var \App\DTO\WorldwideQuote\Export\AggregationField[]
     */
    public array $quote_data_aggregation_fields;

    /**
     * @var \App\DTO\WorldwideQuote\Export\DistributionSummary[]
     */
    public array $quote_data_aggregation;

    /**
     * @Constraints\NotBlank
     */
    public string $list_price;

    /**
     * @Constraints\NotBlank
     */
    public string $final_price;

    /**
     * @Constraints\NotBlank
     */
    public string $applicable_discounts;

    /**
     * @Constraints\NotBlank
     */
    public string $sub_total_value;

    public string $total_value_including_tax;

    public string $grand_total_value;

    public float $quote_price_value_coefficient;

    public string $equipment_address;

    public string $hardware_contact;

    public string $hardware_phone;

    public string $software_address;

    public string $software_contact;

    public string $software_phone;

    /**
     * @Constraints\NotBlank
     */
    public string $coverage_period;

    /**
     * @Constraints\NotBlank
     */
    public string $coverage_period_from;

    /**
     * @Constraints\NotBlank
     */
    public string $coverage_period_to;

    public string $additional_details;

    public string $pricing_document;

    public string $service_agreement_id;

    public string $system_handle;

    public string $footer_notes;

    public string $purchase_order_number = '';

    public string $vat_number = '';
}
