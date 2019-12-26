<?php

namespace Tests\Unit\Discount;

use App\Contracts\Repositories\Quote\Discount\MultiYearDiscountRepositoryInterface;
use Str;

class MultiYearDiscountTest extends DiscountTest
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
            'order_by_durations_duration' => 'asc',
            'order_by_durations_value' => 'asc'
        ]);

        $response = $this->getJson(url("api/discounts/{$this->discountResource()}?" . $query), $this->authorizationHeader);

        $response->assertOk();
    }

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
