<?php

namespace App\DTO\Discounts;

use Spatie\DataTransferObject\DataTransferObjectError;
use Spatie\DataTransferObject\ImmutableDataTransferObject;

/**
 * Class ImmutablePromotionalDiscountData
 *
 * @property-read float $value
 * @property-read float $minimum_limit
 * @property-read float|null $applicableValue
 */
final class ImmutablePromotionalDiscountData extends ImmutableDataTransferObject
{
    public function __construct(PromotionalDiscountData $dataTransferObject)
    {
        parent::__construct($dataTransferObject);
    }

    public function setApplicableValue(float $applicableValue): void
    {
        if (!is_null($this->dataTransferObject->applicableValue)) {
            throw DataTransferObjectError::immutable('applicableValue');
        }

        $this->dataTransferObject->applicableValue = $applicableValue;
    }
}
