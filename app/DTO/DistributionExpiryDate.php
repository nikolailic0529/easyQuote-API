<?php

namespace App\DTO;

use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class DistributionExpiryDate extends DataTransferObject
{
    /**
     * @Constraints\NotBlank()
     * @Constraints\Uuid
     */
    public string $worldwide_distribution_id;

    public Carbon $distribution_expiry_date;
}
