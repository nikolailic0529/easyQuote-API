<?php

namespace Tests\Unit\Discount;

use App\Contracts\Repositories\Quote\Discount\PrePayDiscountRepositoryInterface;
use Str;

class PrePayDiscountTest extends DiscountTest
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

    /**
     * Test creating discount with existing duration attribute.
     *
     * @return void
     */
    public function testExistingDiscountCreating()
    {
        $attributes = $this->makeGenericDiscountAttributes();

        $discount = $this->discountRepository()->create($attributes);

        $response = $this->postJson(url("api/discounts/{$this->discountResource()}"), $attributes, $this->authorizationHeader);

        $response->assertStatus(422)
            ->assertJsonStructure(['Error' => ['original' => ['durations.duration.duration']]]);
    }

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
                'duration' => compact('duration', 'value')
            ],
            'user_id' => $this->user->id
        ];
    }
}
