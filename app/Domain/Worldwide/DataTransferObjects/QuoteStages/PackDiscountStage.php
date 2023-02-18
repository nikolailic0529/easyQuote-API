<?php

namespace App\Domain\Worldwide\DataTransferObjects\QuoteStages;

use App\Domain\Discount\DataTransferObjects\PredefinedDiscounts;
use App\Domain\Worldwide\Enum\PackQuoteStage;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class PackDiscountStage extends DataTransferObject
{
    public PredefinedDiscounts $predefinedDiscounts;

    public ?float $customDiscount = null;

    public int $stage = PackQuoteStage::DISCOUNT;

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
            $context->buildViolation('Custom Discount must be null, when Predefined Discounts are present.')
                ->atPath('customDiscount')
                ->addViolation();
        }
    }
}
