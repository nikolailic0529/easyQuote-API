<?php

namespace Tests\Unit\Discount;

use App\Contracts\Repositories\Quote\Discount\PromotionalDiscountRepositoryInterface;
use Str;

class PromotionalDiscountTest extends DiscountTest
{
    public function testDiscountListing()
    {
        parent::{__FUNCTION__}();

        $query = http_build_query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_country' => 'asc',
            'order_by_vendor' => 'asc',
            'order_by_name' => 'asc',
            'order_by_value' => 'asc',
            'order_by_minimum_limit' => 'asc'
        ]);

        $response = $this->getJson(url("api/discounts/{$this->discountResource()}?" . $query), $this->authorizationHeader);

        $response->assertOk();
    }

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
