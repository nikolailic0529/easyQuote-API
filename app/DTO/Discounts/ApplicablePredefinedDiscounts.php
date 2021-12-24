<?php

namespace App\DTO\Discounts;

use Spatie\DataTransferObject\DataTransferObject;

final class ApplicablePredefinedDiscounts extends DataTransferObject
{
    public ?ImmutableMultiYearDiscountData $multi_year_discount = null;

    public ?ImmutablePrePayDiscountData $pre_pay_discount = null;

    public ?ImmutablePromotionalDiscountData $promotional_discount = null;

    public ?ImmutableSpecialNegotiationDiscountData $special_negotiation_discount = null;
}
