<?php

namespace Tests\Unit\Discount;

use App\Contracts\Repositories\Quote\Discount\PrePayDiscountRepositoryInterface;

class PrePayDiscountTest extends DiscountTest
{
    protected function discountResource(): string
    {
        return 'pre_pay';
    }

    protected function discountRepository()
    {
        return app(PrePayDiscountRepositoryInterface::class);
    }

    protected function makeGenericDiscountAttributes(): array
    {
        $vendor = app('vendor.repository')->random();
        $country = $vendor->load('countries')->countries->random();
        $duration = rand(1, 3);
        $value = number_format(rand(1, 99), 2, '.', '');

        return [
            'name' => "PP {$country->code} {$value}",
            'country_id' => $country->id,
            'vendor_id' => $vendor->id,
            'durations' => [
                compact('duration', 'value')
            ],
            'user_id' => $this->user->id
        ];
    }
}
