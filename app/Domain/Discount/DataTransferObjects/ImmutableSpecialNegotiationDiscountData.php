<?php

namespace App\Domain\Discount\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObjectError;
use Spatie\DataTransferObject\ImmutableDataTransferObject;

/**
 * Class ImmutableSpecialNegotiationDiscountData.
 *
 * @property float      $value
 * @property float|null $applicableValue
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
