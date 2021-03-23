<?php

namespace App\DTO\WorldwideQuote;

use App\DTO\Discounts\ApplicablePredefinedDiscounts;
use App\DTO\Discounts\ImmutableCustomDiscountData;
use App\Models\Quote\WorldwideDistribution;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class DistributorQuoteDiscountData extends DataTransferObject
{
    public WorldwideDistribution $worldwide_distribution;

    public ?int $index = null;

    public ApplicablePredefinedDiscounts $predefined_discounts;

    public ?ImmutableCustomDiscountData $custom_discount;

    /**
     * @Constraints\Callback
     * @param ExecutionContextInterface $context
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
