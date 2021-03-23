<?php


namespace Tests\Feature;


use App\Models\Quote\Discount\MultiYearDiscount;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MultiYearDiscountTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test can view paginated multi year discounts.
     *
     * @return void
     */
    public function testCanViewPaginatedMultiYearDiscounts()
    {
        $this->authenticateApi();

        factory(MultiYearDiscount::class, 30)->create();

        $this->getJson("api/discounts/multi_year")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'country_id',
                        'vendor_id',
                        'name',
                        'durations' => [
                            'duration' => [
                                'value', 'duration',
                            ],
                        ],
                        'country' => [
                            'id', 'iso_3166_2', 'name',
                        ],
                        'vendor' => [
                            'id', 'name', 'short_code',
                        ],
                        'permissions' => [
                            'view', 'update', 'delete',
                        ],
                        'created_at',
                        'updated_at',
                        'activated_at',
                    ],
                ],
            ]);
    }

    /**
     * Test can create a new multi year discount.
     *
     * @return void
     */
    public function testCanCreateMultiYearDiscount()
    {
        MultiYearDiscount::query()->delete();

        $attributes = factory(MultiYearDiscount::class)->raw();

        $this->authenticateApi();

        $this->postJson("api/discounts/multi_year", $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'user_id',
                'country_id',
                'vendor_id',
                'durations' => [
                    'duration' => [
                        'value', 'duration',
                    ],
                ],
                'created_at',
                'updated_at',
                'activated_at',
            ]);
    }

    /**
     * Test can update an existing multi year discount.
     *
     * @return void
     */
    public function testCanUpdateMultiYearDiscount()
    {
        MultiYearDiscount::query()->delete();

        $this->authenticateApi();

        $discount = factory(MultiYearDiscount::class)->create();

        $attributes = factory(MultiYearDiscount::class)->raw();

        $this->patchJson("api/discounts/multi_year/".$discount->getKey(), $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'user_id',
                'country_id',
                'vendor_id',
                'durations' => [
                    'duration' => [
                        'value', 'duration',
                    ],
                ],
                'created_at',
                'updated_at',
                'activated_at',
            ]);
    }

    /**
     * Test can delete an existing multi year discount.
     *
     * @return void
     */
    public function testCanDeleteMultiYearDiscount()
    {
        MultiYearDiscount::query()->delete();

        $this->authenticateApi();

        $discount = factory(MultiYearDiscount::class)->create();

        $this->deleteJson("api/discounts/multi_year/".$discount->getKey())
            ->assertOk()
            ->assertExactJson([true]);

        $this->getJson('api/discounts/multi_year/'.$discount->getKey())
            ->assertNotFound();
    }

    /**
     * Test can activate an existing multi year discount.
     *
     * @return void
     */
    public function testCanActivateMultiYearDiscount()
    {
        MultiYearDiscount::query()->delete();

        $this->authenticateApi();

        $discount = factory(MultiYearDiscount::class)->create();

        $discount->activated_at = null;
        $discount->save();

        $response = $this->getJson('api/discounts/multi_year/'.$discount->getKey())
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));

        $this->putJson('api/discounts/multi_year/activate/'.$discount->getKey())
            ->assertOk();

        $response = $this->getJson('api/discounts/multi_year/'.$discount->getKey())
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertNotEmpty($response->json('activated_at'));
    }

    /**
     * Test can deactivate an existing multi year discount.
     *
     * @return void
     */
    public function testCanDeactivateMultiYearDiscount()
    {
        MultiYearDiscount::query()->delete();

        $this->authenticateApi();

        $discount = factory(MultiYearDiscount::class)->create();

        $discount->activated_at = now();
        $discount->save();

        $response = $this->getJson('api/discounts/multi_year/'.$discount->getKey())
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertNotEmpty($response->json('activated_at'));

        $this->putJson('api/discounts/multi_year/deactivate/'.$discount->getKey())
            ->assertOk();

        $response = $this->getJson('api/discounts/multi_year/'.$discount->getKey())
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));
    }
}
