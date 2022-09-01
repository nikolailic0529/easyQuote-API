<?php

namespace Tests\Feature;

use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PromotionalDiscountTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test can view paginated promotional discounts.
     *
     * @return void
     */
    public function testCanViewPaginatedPromotionalDiscounts()
    {
        $this->authenticateApi();

        factory(PromotionalDiscount::class, 30)->create();

        $this->getJson("api/discounts/promotions")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'country_id',
                        'vendor_id',
                        'name',
                        'value',
                        'minimum_limit',
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
     * Test can create a new promotional discount.
     *
     * @return void
     */
    public function testCanCreatePromotionalDiscount()
    {
        $this->authenticateApi();

        PromotionalDiscount::query()->delete();

        $attributes = factory(PromotionalDiscount::class)->raw();

        $this->postJson("api/discounts/promotions", $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'user_id',
                'country_id',
                'vendor_id',
                'value',
                'minimum_limit',
                'created_at',
                'updated_at',
                'activated_at',
            ]);
    }

    /**
     * Test can update an existing promotional discount.
     *
     * @return void
     */
    public function testCanUpdatePromotionalDiscount()
    {
        $this->authenticateApi();

        PromotionalDiscount::query()->delete();

        $discount = factory(PromotionalDiscount::class)->create();

        $attributes = factory(PromotionalDiscount::class)->raw();

        $this->patchJson("api/discounts/promotions/".$discount->getKey(), $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'user_id',
                'country_id',
                'vendor_id',
                'value',
                'minimum_limit',
                'created_at',
                'updated_at',
                'activated_at',
            ]);
    }

    /**
     * Test can delete an existing promotional discount.
     *
     * @return void
     */
    public function testCanDeletePromotionalDiscount()
    {
        $this->authenticateApi();

        PromotionalDiscount::query()->delete();

        $discount = factory(PromotionalDiscount::class)->create();

        $this->deleteJson("api/discounts/promotions/".$discount->getKey())
            ->assertOk()
            ->assertExactJson([true]);

        $this->getJson('api/discounts/promotions/'.$discount->getKey())
            ->assertNotFound();
    }

    /**
     * Test an ability to delete an existing promotional discount attached to worldwide pack quote.
     *
     * @return void
     */
    public function testCanNotDeletePromotionalDiscountAttachedToWorldwidePackQuote()
    {
        $this->authenticateApi();

        $discount = factory(PromotionalDiscount::class)->create();

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'submitted_at' => now()
        ]);

        $quote->activeVersion->promotionalDiscount()->associate($discount)->save();

        $this->authenticateApi();

        $response = $this->deleteJson("api/discounts/promotions/".$discount->getKey())
//            ->dump()
            ->assertForbidden()
            ->assertJsonStructure([
                'message'
            ]);

        $this->assertStringStartsWith('You can not delete the promotional discount', $response->json('message'));
    }

    /**
     * Test can activate an existing promotional discount.
     *
     * @return void
     */
    public function testCanActivatePromotional()
    {
        $this->authenticateApi();

        PromotionalDiscount::query()->delete();

        $discount = factory(PromotionalDiscount::class)->create();

        $discount->activated_at = null;
        $discount->save();

        $response = $this->getJson('api/discounts/promotions/'.$discount->getKey())
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));

        $this->putJson('api/discounts/promotions/activate/'.$discount->getKey())
            ->assertOk();

        $response = $this->getJson('api/discounts/promotions/'.$discount->getKey())
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertNotEmpty($response->json('activated_at'));
    }

    /**
     * Test can deactivate an existing promotional discount.
     *
     * @return void
     */
    public function testCanDeactivatePromotionalDiscount()
    {
        $this->authenticateApi();

        PromotionalDiscount::query()->delete();

        $discount = factory(PromotionalDiscount::class)->create();

        $discount->activated_at = now();
        $discount->save();

        $response = $this->getJson('api/discounts/promotions/'.$discount->getKey())
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertNotEmpty($response->json('activated_at'));

        $this->putJson('api/discounts/promotions/deactivate/'.$discount->getKey())
            ->assertOk();

        $response = $this->getJson('api/discounts/promotions/'.$discount->getKey())
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));
    }
}
