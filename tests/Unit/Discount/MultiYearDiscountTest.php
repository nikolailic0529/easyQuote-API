<?php

namespace Tests\Unit\Discount;

use App\Models\Quote\Discount\MultiYearDiscount;
use Str;

class MultiYearDiscountTest extends DiscountTest
{
    public function testDiscountListing()
    {
        parent::{__FUNCTION__}();

        $query = http_build_query([
            'search'                        => Str::random(10),
            'order_by_created_at'           => 'asc',
            'order_by_country'              => 'asc',
            'order_by_vendor'               => 'asc',
            'order_by_name'                 => 'asc',
            'order_by_durations_duration'   => 'asc',
            'order_by_durations_value'      => 'asc'
        ]);

        $response = $this->getJson(url("api/discounts/{$this->resource()}?" . $query));

        $response->assertOk();
    }

    /**
     * Test creating discount with existing duration attribute.
     *
     * @return void
     */
    public function testExistingDiscountCreating()
    {
        $attributes = factory($this->model())->raw();

        factory($this->model())->create($attributes);

        $response = $this->postJson(url("api/discounts/{$this->resource()}"), $attributes);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'Error' => ['original' => ['durations.duration.duration']]
            ]);
    }

    protected function resource(): string
    {
        return 'multi_year';
    }

    protected function model(): string
    {
        return MultiYearDiscount::class;
    }
}
