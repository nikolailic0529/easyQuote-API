<?php

namespace App\DTO\QuoteStages;

use App\DTO\Discounts\PredefinedDiscounts;
use App\Enum\PackQuoteStage;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints;

final class PackDiscountStage extends DataTransferObject
{
    public PredefinedDiscounts $predefinedDiscounts;

    public ?float $customDiscount = null;

    public int $stage = PackQuoteStage::DISCOUNT;

    /**
     * @Constraints\Callback
     * @param ExecutionContextInterface $context
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
