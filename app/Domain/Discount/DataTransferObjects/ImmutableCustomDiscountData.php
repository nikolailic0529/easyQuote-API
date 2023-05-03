<?php

namespace App\Domain\Discount\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObjectError;
use Spatie\DataTransferObject\ImmutableDataTransferObject;

/**
 * Class ImmutableCustomDiscountData.
 *
 * @property CustomDiscountData $dataTransferObject
 * @property float              $value
 * @property float|null         $applicableValue
 */
final class ImmutableCustomDiscountData extends ImmutableDataTransferObject
{
    /**
     * ImmutableCustomDiscountData constructor.
     */
    public function __construct(CustomDiscountData $dataTransferObject)
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
