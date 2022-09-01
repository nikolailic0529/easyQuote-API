<?php

namespace Tests\Feature;

use App\Models\Quote\Discount\SND;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SnDiscountTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test can view paginated special negotiation discounts.
     *
     * @return void
     */
    public function testCanViewPaginatedSpecialNegotiationDiscounts()
    {
        $this->authenticateApi();

        factory(SND::class, 30)->create();

        $this->getJson("api/discounts/snd")
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
     * Test can create a new special negotiation discount.
     *
     * @return void
     */
    public function testCanCreateSpecialNegotiationDiscount()
    {
        $this->authenticateApi();

        SND::query()->delete();

        $attributes = factory(SND::class)->raw();

        $this->postJson("api/discounts/snd", $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'user_id',
                'country_id',
                'vendor_id',
                'value',
                'created_at',
                'updated_at',
                'activated_at',
            ]);
    }

    /**
     * Test can update an existing special negotiation discount.
     *
     * @return void
     */
    public function testCanUpdateSpecialNegotiationDiscount()
    {
        $this->authenticateApi();

        SND::query()->delete();

        $discount = factory(SND::class)->create();

        $attributes = factory(SND::class)->raw();

        $this->patchJson("api/discounts/snd/".$discount->getKey(), $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'user_id',
                'country_id',
                'vendor_id',
                'value',
                'created_at',
                'updated_at',
                'activated_at',
            ]);
    }

    /**
     * Test can delete an existing special negotiation discount.
     *
     * @return void
     */
    public function testCanDeleteSpecialNegotiationDiscount()
    {
        $this->authenticateApi();

        SND::query()->delete();

        $discount = factory(SND::class)->create();

        $this->deleteJson("api/discounts/snd/".$discount->getKey())
            ->assertOk()
            ->assertExactJson([true]);

        $this->getJson('api/discounts/snd/'.$discount->getKey())
            ->assertNotFound();
    }

    /**
     * Test an ability to delete an existing special negotiation discount attached to worldwide pack quote.
     *
     * @return void
     */
    public function testCanNotDeleteSpecialNegotiationDiscountAttachedToWorldwidePackQuote()
    {
        $this->authenticateApi();

        $discount = factory(SND::class)->create();

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'submitted_at' => now(),
        ]);

        $quote->activeVersion->snDiscount()->associate($discount)->save();

        $this->authenticateApi();

        $response = $this->deleteJson("api/discounts/snd/".$discount->getKey())
//            ->dump()
            ->assertForbidden()
            ->assertJsonStructure([
                'message',
            ]);

        $this->assertStringStartsWith('You can not delete the special negotiation discount',
            $response->json('message'));
    }

    /**
     * Test can activate an existing special negotiation discount.
     *
     * @return void
     */
    public function testCanActivateSpecialNegotiationDiscount()
    {
        $this->authenticateApi();

        SND::query()->delete();

        $discount = factory(SND::class)->create();

        $discount->activated_at = null;
        $discount->save();

        $response = $this->getJson('api/discounts/snd/'.$discount->getKey())
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));

        $this->putJson('api/discounts/snd/activate/'.$discount->getKey())
            ->assertOk();

        $response = $this->getJson('api/discounts/snd/'.$discount->getKey())
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertNotEmpty($response->json('activated_at'));
    }

    /**
     * Test can deactivate an existing special negotiation discount.
     *
     * @return void
     */
    public function testCanDeactivateSpecialNegotiationDiscount()
    {
        $this->authenticateApi();

        SND::query()->delete();

        $discount = factory(SND::class)->create();

        $discount->activated_at = now();
        $discount->save();

        $response = $this->getJson('api/discounts/snd/'.$discount->getKey())
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertNotEmpty($response->json('activated_at'));

        $this->putJson('api/discounts/snd/deactivate/'.$discount->getKey())
            ->assertOk();

        $response = $this->getJson('api/discounts/snd/'.$discount->getKey())
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));
    }
}
