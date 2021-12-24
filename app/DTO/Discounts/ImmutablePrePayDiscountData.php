<?php

namespace App\DTO\Discounts;

use Spatie\DataTransferObject\DataTransferObjectError;
use Spatie\DataTransferObject\ImmutableDataTransferObject;

/**
 * Class ImmutablePrePayDiscountData
 *
 * @property-read float $value
 * @property-read float|null $applicableValue
 */
final class ImmutablePrePayDiscountData extends ImmutableDataTransferObject
{
    public function __construct(PrePayDiscountData $dataTransferObject)
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
