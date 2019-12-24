<?php

namespace Tests\Unit\Discount;

use App\Contracts\Repositories\Quote\Discount\MultiYearDiscountRepositoryInterface;

class MultiYearDiscountTest extends DiscountTest
{
    protected function discountResource(): string
    {
        return 'multi_year';
    }

    protected function discountRepository()
    {
        return app(MultiYearDiscountRepositoryInterface::class);
    }

    protected function makeGenericDiscountAttributes(): array
    {
        $vendor = app('vendor.repository')->random();
        $country = $vendor->load('countries')->countries->random();
        $duration = rand(1, 5);
        $value = number_format(rand(1, 99), 2, '.', '');

        return [
            'name' => "MY {$country->code} {$value}",
            'country_id' => $country->id,
            'vendor_id' => $vendor->id,
            'durations' => [
                compact('duration', 'value')
            ],
            'user_id' => $this->user->id
        ];
    }
}
