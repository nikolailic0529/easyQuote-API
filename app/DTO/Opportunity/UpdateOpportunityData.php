<?php

namespace App\DTO\Opportunity;

use App\DTO\MissingValue;
use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdateOpportunityData extends DataTransferObject
{
    /** @var string|\App\DTO\MissingValue */
    public string|MissingValue $sales_unit_id;

    /** @var string|\App\DTO\MissingValue */
    public string|MissingValue $pipeline_id;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $pipeline_stage_id;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $contract_type_id;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $account_manager_id;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $primary_account_id;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $end_user_id;

    /** @var bool|\App\DTO\MissingValue */
    public bool|MissingValue $are_end_user_addresses_available;

    /** @var bool|\App\DTO\MissingValue  */
    public bool|MissingValue $are_end_user_contacts_available;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $primary_account_contact_id;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $project_name;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $nature_of_service;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $renewal_month;

    /** @var int|\App\DTO\MissingValue|null */
    public int|MissingValue|null $renewal_year;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $customer_status;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $end_user_name;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $hardware_status;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $region_name;

    /** @var \Carbon\Carbon|\App\DTO\MissingValue|null */
    public Carbon|MissingValue|null $opportunity_start_date;

    /** @var bool|\App\DTO\MissingValue */
    public bool|MissingValue $is_opportunity_start_date_assumed = false;

    /** @var \Carbon\Carbon|\App\DTO\MissingValue|null */
    public Carbon|MissingValue|null $opportunity_end_date;

    /** @var bool|\App\DTO\MissingValue */
    public bool|MissingValue $is_opportunity_end_date_assumed = false;

    /** @var \Carbon\Carbon|\App\DTO\MissingValue|null */
    public Carbon|MissingValue|null $opportunity_closing_date;

    /** @var int|\App\DTO\MissingValue|null */
    public int|MissingValue|null $contract_duration_months;

    /** @var bool|\App\DTO\MissingValue */
    public bool|MissingValue $is_contract_duration_checked = false;

    /** @var \Carbon\Carbon|\App\DTO\MissingValue|null */
    public Carbon|MissingValue|null $expected_order_date;

    /** @var \Carbon\Carbon|\App\DTO\MissingValue|null */
    public Carbon|MissingValue|null $customer_order_date;

    /** @var \Carbon\Carbon|\App\DTO\MissingValue|null */
    public Carbon|MissingValue|null $purchase_order_date;

    /** @var \Carbon\Carbon|\App\DTO\MissingValue|null */
    public Carbon|MissingValue|null $supplier_order_date;

    /** @var \Carbon\Carbon|\App\DTO\MissingValue|null */
    public Carbon|MissingValue|null $supplier_order_transaction_date;

    /** @var \Carbon\Carbon|\App\DTO\MissingValue|null */
    public Carbon|MissingValue|null $supplier_order_confirmation_date;

    /** @var float|\App\DTO\MissingValue|null */
    public float|MissingValue|null $opportunity_amount;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $opportunity_amount_currency_code;

    /** @var float|\App\DTO\MissingValue|null */
    public float|MissingValue|null $purchase_price;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $purchase_price_currency_code;

    /** @var float|\App\DTO\MissingValue|null */
    public float|MissingValue|null $list_price;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $list_price_currency_code;

    /** @var float|\App\DTO\MissingValue|null */
    public float|MissingValue|null $estimated_upsell_amount;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $estimated_upsell_amount_currency_code;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $personal_rating;

    /** @var int|\App\DTO\MissingValue|null */
    public int|MissingValue|null $ranking;

    /** @var float|\App\DTO\MissingValue|null */
    public float|MissingValue|null $margin_value;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $competition_name;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $service_level_agreement_id;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $sale_unit_name;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $drop_in;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $lead_source_name;

    /** @var bool|\App\DTO\MissingValue */
    public bool|MissingValue $has_higher_sla;

    /** @var bool|\App\DTO\MissingValue */
    public bool|MissingValue $is_multi_year;

    /** @var bool|\App\DTO\MissingValue */
    public bool|MissingValue $has_additional_hardware;

    /** @var bool|\App\DTO\MissingValue */
    public bool|MissingValue $has_service_credits;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $remarks;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $notes;

    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $campaign_name;

    /**
     * @var \App\DTO\Opportunity\CreateSupplierData[]|\App\DTO\MissingValue
     */
    public array|MissingValue $create_suppliers;

    /**
     * @var \App\DTO\Opportunity\UpdateSupplierData[]|\App\DTO\MissingValue
     */
    public array|MissingValue $update_suppliers;

    /** @var \App\DTO\Opportunity\CreateOpportunityRecurrenceData|\App\DTO\MissingValue|null */
    public CreateOpportunityRecurrenceData|MissingValue|null $recurrence;

    public function __construct(array $parameters = [])
    {
        $class = new \ReflectionClass(static::class);
        $missing = new MissingValue();

        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if (key_exists($property->getName(), $parameters) === false) {
                $parameters[$property->getName()] = $missing;
            }
        }

        parent::__construct($parameters);
    }
}
