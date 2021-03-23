<?php

namespace App\DTO\Opportunity;

use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdateOpportunityData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $contract_type_id;

    /**
     * @Constraints\Uuid
     */
    public ?string $account_manager_id;

    /**
     * @Constraints\Uuid
     */
    public ?string $primary_account_id = null;

    /**
     * @Constraints\Uuid
     */
    public ?string $primary_account_contact_id = null;

    /**
     * @Constraints\Length(
     *      max=191
     * )
     */
    public ?string $project_name = null;

    /**
     * @Constraints\Length(
     *     max=191
     * )
     */
    public ?string $nature_of_service = null;

    /**
     * @Constraints\NotBlank(
     *    allowNull=true
     * )
     */
    public ?string $renewal_month = null;

    /**
     * @Constraints\PositiveOrZero
     */
    public ?int $renewal_year = null;

    /**
     * @Constraints\Length(
     *     max=191
     * )
     */
    public ?string $customer_status = null;

    /**
     * @Constraints\Length(
     *     max=191
     * )
     */
    public ?string $end_user_name = null;

    /**
     * @Constraints\Length(
     *     max=191
     * )
     */
    public ?string $hardware_status = null;

    /**
     * @Constraints\Length(
     *     max=191
     * )
     */
    public ?string $region_name = null;

    public Carbon $opportunity_start_date;

    public Carbon $opportunity_end_date;

    public Carbon $opportunity_closing_date;

    public ?Carbon $expected_order_date = null;

    public ?Carbon $customer_order_date = null;

    public ?Carbon $purchase_order_date = null;

    public ?Carbon $supplier_order_date = null;

    public ?Carbon $supplier_order_transaction_date = null;

    public ?Carbon $supplier_order_confirmation_date = null;

    public ?float $opportunity_amount = null;

    /**
     * @Constraints\Length(
     *     min=3,
     *     max=3
     * )
     */
    public ?string $opportunity_amount_currency_code = null;

    public ?float $purchase_price = null;

    /**
     * @Constraints\Length(
     *     min=3,
     *     max=3
     * )
     */
    public ?string $purchase_price_currency_code = null;

    public ?float $list_price = null;

    /**
     * @Constraints\Length(
     *     min=3,
     *     max=3
     * )
     */
    public ?string $list_price_currency_code = null;

    public ?float $estimated_upsell_amount = null;

    /**
     * @Constraints\Length(
     *     min=3,
     *     max=3
     * )
     */
    public ?string $estimated_upsell_amount_currency_code = null;

    /**
     * @Constraints\NotBlank(allowNull=true)
     */
    public ?string $personal_rating = null;

    public ?float $ranking = null;

    public ?float $margin_value = null;

    /**
     * @Constraints\Length(
     *     max=191
     * )
     */
    public ?string $competition_name = null;

    /**
     * @Constraints\Length(
     *     max=191
     * )
     */
    public ?string $service_level_agreement_id = null;

    /**
     * @Constraints\Length(
     *     max=191
     * )
     */
    public ?string $sale_unit_name = null;

    /**
     * @Constraints\Length(
     *     max=191
     * )
     */
    public ?string $drop_in = null;

    /**
     * @Constraints\Length(
     *     max=191
     * )
     */
    public ?string $lead_source_name = null;

    public bool $has_higher_sla = false;

    public bool $is_multi_year = false;

    public bool $has_additional_hardware = false;

    /**
     * @Constraints\Length(
     *     max=10000
     * )
     */
    public ?string $remarks = null;

    /**
     * @Constraints\Length(
     *     max=10000
     * )
     */
    public ?string $notes = null;

    /**
     * @Constraints\Length(
     *     max=191
     * )
     */
    public ?string $sale_action_name = null;

    /**
     * @var \App\DTO\Opportunity\CreateSupplierData[]
     */
    public array $create_suppliers;

    /**
     * @var \App\DTO\Opportunity\UpdateSupplierData[]
     */
    public array $update_suppliers;
}
