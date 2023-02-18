<?php

namespace App\Domain\Worldwide\DataTransferObjects\Opportunity;

use App\Foundation\DataTransferObject\MissingValue;
use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;

final class UpdateOpportunityData extends DataTransferObject
{
    protected array $dates = [
        'opportunity_start_date' => 'Y-m-d',
        'opportunity_end_date' => 'Y-m-d',
        'opportunity_closing_date' => 'Y-m-d',
        'expected_order_date' => 'Y-m-d',
        'customer_order_date' => 'Y-m-d',
        'purchase_order_date' => 'Y-m-d',
        'supplier_order_date' => 'Y-m-d',
        'supplier_order_transaction_date' => 'Y-m-d',
        'supplier_order_confirmation_date' => 'Y-m-d',
    ];

    /** @var string|\App\Foundation\DataTransferObject\MissingValue */
    public string|MissingValue $sales_unit_id;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue */
    public string|MissingValue $pipeline_id;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $pipeline_stage_id;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $contract_type_id;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $account_manager_id;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $primary_account_id;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $end_user_id;

    /** @var bool|\App\Foundation\DataTransferObject\MissingValue */
    public bool|MissingValue $are_end_user_addresses_available;

    /** @var bool|\App\Foundation\DataTransferObject\MissingValue  */
    public bool|MissingValue $are_end_user_contacts_available;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $primary_account_contact_id;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $project_name;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $nature_of_service;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $renewal_month;

    /** @var int|\App\Foundation\DataTransferObject\MissingValue|null */
    public int|MissingValue|null $renewal_year;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $customer_status;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $end_user_name;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $hardware_status;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $region_name;

    /** @var \Carbon\Carbon|\App\Foundation\DataTransferObject\MissingValue|null */
    public Carbon|MissingValue|null $opportunity_start_date;

    /** @var bool|\App\Foundation\DataTransferObject\MissingValue */
    public bool|MissingValue $is_opportunity_start_date_assumed = false;

    /** @var \Carbon\Carbon|\App\Foundation\DataTransferObject\MissingValue|null */
    public Carbon|MissingValue|null $opportunity_end_date;

    /** @var bool|\App\Foundation\DataTransferObject\MissingValue */
    public bool|MissingValue $is_opportunity_end_date_assumed = false;

    /** @var \Carbon\Carbon|\App\Foundation\DataTransferObject\MissingValue|null */
    public Carbon|MissingValue|null $opportunity_closing_date;

    /** @var int|\App\Foundation\DataTransferObject\MissingValue|null */
    public int|MissingValue|null $contract_duration_months;

    /** @var bool|\App\Foundation\DataTransferObject\MissingValue */
    public bool|MissingValue $is_contract_duration_checked = false;

    /** @var \Carbon\Carbon|\App\Foundation\DataTransferObject\MissingValue|null */
    public Carbon|MissingValue|null $expected_order_date;

    /** @var \Carbon\Carbon|\App\Foundation\DataTransferObject\MissingValue|null */
    public Carbon|MissingValue|null $customer_order_date;

    /** @var \Carbon\Carbon|\App\Foundation\DataTransferObject\MissingValue|null */
    public Carbon|MissingValue|null $purchase_order_date;

    /** @var \Carbon\Carbon|\App\Foundation\DataTransferObject\MissingValue|null */
    public Carbon|MissingValue|null $supplier_order_date;

    /** @var \Carbon\Carbon|\App\Foundation\DataTransferObject\MissingValue|null */
    public Carbon|MissingValue|null $supplier_order_transaction_date;

    /** @var \Carbon\Carbon|\App\Foundation\DataTransferObject\MissingValue|null */
    public Carbon|MissingValue|null $supplier_order_confirmation_date;

    /** @var float|\App\Foundation\DataTransferObject\MissingValue|null */
    public float|MissingValue|null $opportunity_amount;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $opportunity_amount_currency_code;

    /** @var float|\App\Foundation\DataTransferObject\MissingValue|null */
    public float|MissingValue|null $purchase_price;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $purchase_price_currency_code;

    /** @var float|\App\Foundation\DataTransferObject\MissingValue|null */
    public float|MissingValue|null $list_price;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $list_price_currency_code;

    /** @var float|\App\Foundation\DataTransferObject\MissingValue|null */
    public float|MissingValue|null $estimated_upsell_amount;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $estimated_upsell_amount_currency_code;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $personal_rating;

    /** @var int|\App\Foundation\DataTransferObject\MissingValue|null */
    public int|MissingValue|null $ranking;

    /** @var float|\App\Foundation\DataTransferObject\MissingValue|null */
    public float|MissingValue|null $margin_value;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $competition_name;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $service_level_agreement_id;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $sale_unit_name;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $drop_in;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $lead_source_name;

    /** @var bool|\App\Foundation\DataTransferObject\MissingValue */
    public bool|MissingValue $has_higher_sla;

    /** @var bool|\App\Foundation\DataTransferObject\MissingValue */
    public bool|MissingValue $is_multi_year;

    /** @var bool|\App\Foundation\DataTransferObject\MissingValue */
    public bool|MissingValue $has_additional_hardware;

    /** @var bool|\App\Foundation\DataTransferObject\MissingValue */
    public bool|MissingValue $has_service_credits;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $remarks;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $notes;

    /** @var string|\App\Foundation\DataTransferObject\MissingValue|null */
    public string|MissingValue|null $campaign_name;

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Opportunity\CreateSupplierData[]|\App\Foundation\DataTransferObject\MissingValue
     */
    public array|MissingValue $create_suppliers;

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Opportunity\UpdateSupplierData[]|\App\Foundation\DataTransferObject\MissingValue
     */
    public array|MissingValue $update_suppliers;

    /** @var \App\Domain\Worldwide\DataTransferObjects\Opportunity\CreateOpportunityRecurrenceData|\App\Foundation\DataTransferObject\MissingValue|null */
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

    public function toArray(): array
    {
        $array = parent::toArray();

        foreach ($array as $key => $value) {
            if (key_exists($key, $this->dates) && $value instanceof \DateTimeInterface) {
                $array[$key] = $value->format('Y-m-d');
            }
        }
        return $array;
    }
}
