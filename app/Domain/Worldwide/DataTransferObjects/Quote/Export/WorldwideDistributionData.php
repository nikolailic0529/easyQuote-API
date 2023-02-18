<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote\Export;

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

    public DistributionSupplierData $supplier;

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Quote\Export\AssetData[]|\App\Domain\Worldwide\DataTransferObjects\Quote\Export\AssetsGroupData[]
     */
    public array $assets_data;

    public int $mapped_fields_count;

    public bool $assets_are_grouped;

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Quote\Export\AssetField[]
     */
    public array $asset_fields;

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Quote\Export\PaymentScheduleField[]
     */
    public array $payment_schedule_fields;

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Quote\Export\PaymentData[]
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

    public string $coverage_period;

    public string $coverage_period_from;

    public string $coverage_period_to;

    public string $contract_duration;

    public bool $is_contract_duration_checked;

    public string $additional_details;

    public string $pricing_document;

    public string $service_agreement_id;

    public string $system_handle;

    public string $purchase_order_number;

    public string $vat_number;

    public string $asset_notes;
}
