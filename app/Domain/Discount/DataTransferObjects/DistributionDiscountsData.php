<?php

namespace App\Domain\Discount\DataTransferObjects;

use App\Domain\Worldwide\Models\WorldwideDistribution;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class DistributionDiscountsData extends DataTransferObject
{
    public WorldwideDistribution $worldwideDistribution;

    public PredefinedDiscounts $predefinedDiscounts;

    public ?float $customDiscount = null;

    /**
     * @Constraints\Callback
     */
    public function validate(ExecutionContextInterface $context)
    {
        if (!isset($this->customDiscount)) {
            return;
        }

        if (
            isset($this->predefinedDiscounts->multiYearDiscount) ||
            isset($this->predefinedDiscounts->prePayDiscount) ||
            isset($this->predefinedDiscounts->promotionalDiscount) ||
            isset($this->predefinedDiscounts->snDiscount)
        ) {
            $context->buildViolation('Custom Discount should be null, when Predefined Discounts are present.')
                ->atPath('customDiscount')
                ->addViolation();
        }
    }
}
