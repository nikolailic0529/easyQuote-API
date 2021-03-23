<?php


namespace Tests\Feature;


use App\Models\Quote\Discount\PrePayDiscount;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\Traits\WithFakeUser;

class PrePayDiscountTest extends TestCase
{
    use WithFakeUser, DatabaseTransactions;

    /**
     * Test can view paginated pre pay discounts.
     *
     * @return void
     */
    public function testCanViewPaginatedPrePayDiscounts()
    {
        factory(PrePayDiscount::class, 30)->create();

        $this->getJson("api/discounts/pre_pay")
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
     * Test can create a new pre pay discount.
     *
     * @return void
     */
    public function testCanCreatePrePayDiscount()
    {
        PrePayDiscount::query()->delete();

        $attributes = factory(PrePayDiscount::class)->raw();

        $this->postJson("api/discounts/pre_pay", $attributes)
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
     * Test can update an existing pre pay discount.
     *
     * @return void
     */
    public function testCanUpdatePrePayDiscount()
    {
        PrePayDiscount::query()->delete();

        $discount = factory(PrePayDiscount::class)->create();

        $attributes = factory(PrePayDiscount::class)->raw();

        $this->patchJson("api/discounts/pre_pay/".$discount->getKey(), $attributes)
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
     * Test can delete an existing pre pay discount.
     *
     * @return void
     */
    public function testCanDeletePrePayDiscount()
    {
        PrePayDiscount::query()->delete();

        $discount = factory(PrePayDiscount::class)->create();

        $this->deleteJson("api/discounts/pre_pay/".$discount->getKey())
            ->assertOk()
            ->assertExactJson([true]);

        $this->getJson('api/discounts/pre_pay/'.$discount->getKey())
            ->assertNotFound();
    }

    /**
     * Test can activate an existing pre pay discount.
     *
     * @return void
     */
    public function testCanActivatePrePayDiscount()
    {
        PrePayDiscount::query()->delete();

        $discount = factory(PrePayDiscount::class)->create();

        $discount->activated_at = null;
        $discount->save();

        $response = $this->getJson('api/discounts/pre_pay/'.$discount->getKey())
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));

        $this->putJson('api/discounts/pre_pay/activate/'.$discount->getKey())
            ->assertOk();

        $response = $this->getJson('api/discounts/pre_pay/'.$discount->getKey())
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertNotEmpty($response->json('activated_at'));
    }

    /**
     * Test can deactivate an existing pre pay discount.
     *
     * @return void
     */
    public function testCanDeactivatePrePayDiscount()
    {
        PrePayDiscount::query()->delete();

        $discount = factory(PrePayDiscount::class)->create();

        $discount->activated_at = now();
        $discount->save();

        $response = $this->getJson('api/discounts/pre_pay/'.$discount->getKey())
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertNotEmpty($response->json('activated_at'));

        $this->putJson('api/discounts/pre_pay/deactivate/'.$discount->getKey())
            ->assertOk();

        $response = $this->getJson('api/discounts/pre_pay/'.$discount->getKey())
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));
    }
}
