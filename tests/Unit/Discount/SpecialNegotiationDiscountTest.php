<?php

namespace Tests\Unit\Discount;

use App\Models\Quote\Discount\SND;
use Str;

class SpecialNegotiationDiscountTest extends DiscountTest
{
    public function testDiscountListing()
    {
        parent::{__FUNCTION__}();

        $query = http_build_query([
            'search'                => Str::random(10),
            'order_by_created_at'   => 'asc',
            'order_by_country'      => 'asc',
            'order_by_vendor'       => 'asc',
            'order_by_name'         => 'asc',
            'order_by_value'        => 'asc'
        ]);

        $response = $this->getJson(url("api/discounts/{$this->resource()}?" . $query));

        $response->assertOk();
    }

    protected function resource(): string
    {
        return 'snd';
    }

    protected function model(): string
    {
        return SND::class;
    }
}
