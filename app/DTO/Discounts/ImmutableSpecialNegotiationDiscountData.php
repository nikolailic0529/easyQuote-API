<?php

namespace App\DTO\Discounts;

use Spatie\DataTransferObject\DataTransferObjectError;
use Spatie\DataTransferObject\ImmutableDataTransferObject;

/**
 * Class ImmutableSpecialNegotiationDiscountData
 *
 * @property-read float $value
 * @property-read float|null $applicableValue
 */
final class ImmutableSpecialNegotiationDiscountData extends ImmutableDataTransferObject
{
    public function __construct(SpecialNegotiationDiscountData $dataTransferObject)
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
