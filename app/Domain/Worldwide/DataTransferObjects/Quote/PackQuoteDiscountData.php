<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use App\Domain\Discount\DataTransferObjects\ApplicablePredefinedDiscounts;
use App\Domain\Discount\DataTransferObjects\ImmutableCustomDiscountData;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class PackQuoteDiscountData extends DataTransferObject
{
    public ApplicablePredefinedDiscounts $predefined_discounts;

    public ?ImmutableCustomDiscountData $custom_discount;

    /**
     * @Constraints\Callback
     */
    public function validate(ExecutionContextInterface $context)
    {
        if (!isset($this->custom_discount)) {
            return;
        }

        if (
            isset($this->predefined_discounts->multi_year_discount) ||
            isset($this->predefined_discounts->pre_pay_discount) ||
            isset($this->predefined_discounts->promotional_discount) ||
            isset($this->predefined_discounts->special_negotiation_discount)
        ) {
            $context->buildViolation('Custom Discount must be null, when Predefined Discounts are present.')
                ->atPath('customDiscount')
                ->addViolation();
        }
    }
}
