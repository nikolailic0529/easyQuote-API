<?php

namespace App\DTO\WorldwideQuote\Export;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class WorldwideDistributionData extends DataTransferObject
{
    /**
     * @Constraints\NotBlank
     */
    public string $vendors;

    /**
     * @Constraints\NotBlank
     */
    public string $country;

    /**
     * @var \App\DTO\WorldwideQuote\Export\AssetData[]|\App\DTO\WorldwideQuote\Export\AssetsGroupData[]
     */
    public array $assets_data;

    public int $mapped_fields_count;

    public bool $assets_are_grouped;

    /**
     * @var \App\DTO\WorldwideQuote\Export\AssetField[]
     */
    public array $asset_fields;

    /**
     * @var \App\DTO\WorldwideQuote\Export\PaymentScheduleField[]
     */
    public array $payment_schedule_fields;

    /**
     * @var \App\DTO\WorldwideQuote\Export\PaymentData[]
     */
    public array $payment_schedule_data;

    public bool $has_payment_schedule_data;

    public string $equipment_address;

    public string $hardware_contact;

    public string $hardware_phone;

    public string $software_address;

    public string $software_contact;

    public string $software_phone;

    public string $service_levels;

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

    public string $purchase_order_number;

    public string $vat_number;
}
