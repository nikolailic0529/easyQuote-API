<?php

namespace App\Domain\Worldwide\DataTransferObjects\DistributorQuote;

use App\Domain\Worldwide\Models\WorldwideDistribution;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class DistributionDetailsData extends DataTransferObject
{
    public WorldwideDistribution $worldwide_distribution;

    /**
     * @Constraints\NotBlank
     */
    public string $pricing_document;

    /**
     * @Constraints\NotBlank
     */
    public string $service_agreement_id;

    /**
     * @Constraints\NotBlank
     */
    public string $system_handle;

    /**
     * @Constraints\NotBlank
     */
    public string $purchase_order_number;

    /**
     * @Constraints\NotBlank
     */
    public string $vat_number;

    public ?string $additional_details;
}
