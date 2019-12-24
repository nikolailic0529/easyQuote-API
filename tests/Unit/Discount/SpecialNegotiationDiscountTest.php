<?php

namespace Tests\Unit\Discount;

use App\Contracts\Repositories\Quote\Discount\SNDrepositoryInterface;

class SpecialNegotiationDiscountTest extends DiscountTest
{
    protected function discountResource(): string
    {
        return 'snd';
    }

    protected function discountRepository()
    {
        return app(SNDrepositoryInterface::class);
    }

    protected function makeGenericDiscountAttributes(): array
    {
        $vendor = app('vendor.repository')->random();
        $country = $vendor->load('countries')->countries->random();
        $value = number_format(rand(1, 99), 2, '.', '');

        return [
            'name' => "SN {$country->code} {$value}",
            'country_id' => $country->id,
            'vendor_id' => $vendor->id,
            'value' => $value,
            'user_id' => $this->user->id
        ];
    }
}
