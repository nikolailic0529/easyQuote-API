<?php

namespace Tests\Unit\Discount;

use App\Contracts\Repositories\Quote\Discount\PromotionalDiscountRepositoryInterface;

class PromotionalDiscountTest extends DiscountTest
{
    protected function discountResource(): string
    {
        return 'promotions';
    }

    protected function discountRepository()
    {
        return app(PromotionalDiscountRepositoryInterface::class);
    }

    protected function makeGenericDiscountAttributes(): array
    {
        $vendor = app('vendor.repository')->random();
        $country = $vendor->load('countries')->countries->random();
        $value = number_format(rand(1, 99), 2, '.', '');
        $minimum_limit = rand(1, 3);

        return [
            'name' => "PD {$country->code} {$value}",
            'country_id' => $country->id,
            'vendor_id' => $vendor->id,
            'value' => $value,
            'minimum_limit' => $minimum_limit,
            'user_id' => $this->user->id
        ];
    }
}
