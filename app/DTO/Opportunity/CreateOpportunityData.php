<?php

namespace App\DTO\Opportunity;

use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class CreateOpportunityData extends DataTransferObject
{
    #[Constraints\Uuid]
    public ?string $user_id = null;

    #[Constraints\Uuid]
    public string $sales_unit_id;

    #[Constraints\Uuid]
    public string $pipeline_id;

    #[Constraints\Uuid]
    public ?string $pipeline_stage_id;

    #[Constraints\Uuid]
    public ?string $contract_type_id;

    #[Constraints\Uuid]
    public ?string $account_manager_id;

    #[Constraints\Uuid]
    public ?string $primary_account_id;

    #[Constraints\Uuid]
    public ?string $end_user_id;

    public bool $are_end_user_addresses_available = false;

    public bool $are_end_user_contacts_available = false;

    #[Constraints\Uuid]
    public ?string $primary_account_contact_id;

    #[Constraints\Length(max: 191)]
    public ?string $project_name;

    #[Constraints\Length(max: 191)]
    public ?string $nature_of_service;

    #[Constraints\NotBlank(allowNull: true)]
    public ?string $renewal_month;

    #[Constraints\PositiveOrZero]
    public ?int $renewal_year;

    #[Constraints\Length(max: 191)]
    public ?string $customer_status;

    #[Constraints\Length(max: 191)]
    public ?string $end_user_name;

    #[Constraints\Length(max: 191)]
    public ?string $hardware_status;

    #[Constraints\Length(max: 191)]
    public ?string $region_name;

    public ?Carbon $opportunity_start_date;

    public bool $is_opportunity_start_date_assumed = false;

    public ?Carbon $opportunity_end_date;

    public bool $is_opportunity_end_date_assumed = false;

    public ?Carbon $opportunity_closing_date;

    public ?int $contract_duration_months;

    public bool $is_contract_duration_checked = false;

    public ?Carbon $expected_order_date;

    public ?Carbon $customer_order_date;

    public ?Carbon $purchase_order_date;

    public ?Carbon $supplier_order_date;

    public ?Carbon $supplier_order_transaction_date;

    public ?Carbon $supplier_order_confirmation_date;

    public ?float $opportunity_amount;

    public ?float $base_opportunity_amount;

    #[Constraints\Length(exactly: 3)]
    public ?string $opportunity_amount_currency_code;

    public ?float $purchase_price;

    public ?float $base_purchase_price;

    #[Constraints\Length(exactly: 3)]
    public ?string $purchase_price_currency_code;

    public ?float $list_price;

    public ?float $base_list_price;

    #[Constraints\Length(exactly: 3)]
    public ?string $list_price_currency_code;

    public ?float $estimated_upsell_amount;

    #[Constraints\Length(exactly: 3)]
    public ?string $estimated_upsell_amount_currency_code;

    #[Constraints\NotBlank(allowNull: true)]
    public ?string $personal_rating;

    public ?int $ranking;

    public ?float $margin_value;

    #[Constraints\Length(max: 191)]
    public ?string $competition_name;

    #[Constraints\Length(max: 191)]
    public ?string $service_level_agreement_id;

    #[Constraints\Length(max: 191)]
    public ?string $sale_unit_name;

    #[Constraints\Length(max: 191)]
    public ?string $drop_in;

    #[Constraints\Length(max: 191)]
    public ?string $lead_source_name;

    public bool $has_higher_sla;

    public bool $is_multi_year;

    public bool $has_additional_hardware;
    
    public bool $has_service_credits;

    #[Constraints\Length(max: 10_000)]
    public ?string $remarks;

    #[Constraints\Length(max: 10_000)]
    public ?string $notes;

    #[Constraints\Length(max: 191)]
    public ?string $campaign_name;

    /**
     * @var \App\DTO\Opportunity\CreateSupplierData[]
     */
    public array $create_suppliers;

    public ?CreateOpportunityRecurrenceData $recurrence;
}
