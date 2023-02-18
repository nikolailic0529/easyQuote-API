<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote\Export;

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

    public string $sales_order_number;

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

    public string $contact_country;

    public string $end_user_name;

    public string $end_user_contact_country;

    public string $end_user_contact_name;

    public string $end_user_contact_email;

    public string $end_user_company_email;

    public string $end_user_hw_post_code;

    public string $end_user_inv_post_code;

    public string $account_manager_name;

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Quote\Export\AggregationField[]
     */
    public array $quote_data_aggregation_fields;

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Quote\Export\QuoteAggregation[]
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

    public string $coverage_period;

    public string $coverage_period_from;

    public string $coverage_period_to;

    public string $contract_duration;

    public bool $is_contract_duration_checked;

    public string $additional_details;

    public string $pricing_document;

    public string $service_agreement_id;

    public string $system_handle;

    public string $footer_notes;

    public string $purchase_order_number = '';

    public string $vat_number = '';
}
