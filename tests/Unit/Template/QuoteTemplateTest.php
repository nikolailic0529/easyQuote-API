<?php

namespace Tests\Unit\Template;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\{
    WithFakeUser,
    AssertsListing
};
use Str, Arr;

class QuoteTemplateTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser, AssertsListing;

    protected static $assertableAttributes = ['name', 'company_id', 'vendor_id', 'currency_id', 'form_data'];

    /**
     * Test Template listing.
     *
     * @return void
     */
    public function testTemplateListing()
    {
        $response = $this->getJson(url("api/templates"));

        $this->assertListing($response);

        $query = http_build_query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_name' => 'asc',
            'order_by_company_name' => 'asc',
            'order_by_vendor_name' => 'asc',
        ]);

        $response = $this->getJson(url("api/templates?{$query}"));

        $response->assertOk();
    }

    /**
     * Test Template creating with valid attributes.
     *
     * @return void
     */
    public function testTemplateCreating()
    {
        $attributes = $this->makeGenericTemplateAttributes();

        $response = $this->postJson(url("api/templates"), $attributes);

        $response->assertOk()
            ->assertJsonStructure(static::$assertableAttributes)
            ->assertJsonFragment(Arr::only($attributes, static::$assertableAttributes));
    }

    /**
     * Test updating a newly created Template with valid attributes.
     *
     * @return void
     */
    public function testTemplateUpdating()
    {
        $template = app('template.repository')->create($this->makeGenericTemplateAttributes());

        $attributes = $this->makeGenericTemplateAttributes();

        $response = $this->patchJson(url("api/templates/{$template->id}"), $attributes);

        $response->assertOk()
            ->assertJsonStructure(static::$assertableAttributes)
            ->assertJsonFragment(Arr::only($attributes, static::$assertableAttributes));
    }

    /**
     * Test updating a Master Template. Updating of any Master Templates is forbidden.
     *
     * @return void
     */
    public function testMasterTemplateUpdating()
    {
        $template = app('template.repository')->random(1, function ($query) {
            $query->system();
        });

        $attributes = $this->makeGenericTemplateAttributes();

        $response = $this->patchJson(url("api/templates/{$template->id}"), $attributes);

        $response->assertForbidden()
            ->assertJsonFragment(['message' => QTSU_01]);
    }

    /**
     * Test copying an existing Master Template.
     *
     * @return void
     */
    public function testTemplateCopying()
    {
        $template = app('template.repository')->random(1, function ($query) {
            $query->system();
        });

        $response = $this->putJson(url("api/templates/copy/{$template->id}"), []);

        $response->assertOk();

        /**
         * Test that a newly copied Template existing.
         */
        $id = $response->json('id');
        $response = $this->getJson(url("api/templates/{$id}"));

        $response->assertOk()
            ->assertJsonStructure(static::$assertableAttributes);
    }

    /**
     * Test deleting a newly created Template.
     *
     * @return void
     */
    public function testTemplateDeleting()
    {
        $template = app('template.repository')->create($this->makeGenericTemplateAttributes());

        $response = $this->deleteJson(url("api/templates/{$template->id}"), []);

        $response->assertOk()
            ->assertExactJson([true]);

        $template->refresh();

        $this->assertNotNull($template->deleted_at);
    }

    /**
     * Test deleting a Master Template. Deleting Master Template is forbidden.
     *
     * @return void
     */
    public function testMasterTemplateDeleting()
    {
        $template = app('template.repository')->random(1, function ($query) {
            $query->system();
        });

        $response = $this->deleteJson(url("api/templates/{$template->id}"), []);

        $response->assertForbidden()
            ->assertJsonFragment(['message' => QTSD_01]);

        $template->refresh();

        $this->assertNull($template->deleted_at);
    }

    /**
     * Test activating a newly created Template.
     *
     * @return void
     */
    public function testTemplateActivating()
    {
        $template = tap(app('template.repository')->create($this->makeGenericTemplateAttributes()))->deactivate();

        $response = $this->putJson(url("api/templates/activate/{$template->id}"), []);

        $response->assertOk()
            ->assertExactJson([true]);

        $template->refresh();

        $this->assertNotNull($template->activated_at);
    }

    /**
     * Test deactivating a newly created Template.
     *
     * @return void
     */
    public function testTemplateDeactivating()
    {
        $template = tap(app('template.repository')->create($this->makeGenericTemplateAttributes()))->activate();

        $response = $this->putJson(url("api/templates/deactivate/{$template->id}"), []);

        $response->assertOk()
            ->assertExactJson([true]);

        $template->refresh();

        $this->assertNull($template->activated_at);
    }

    /**
     * Test designer data for a specified Template.
     *
     * @return void
     */
    public function testTemplateDesigner()
    {
        $template = app('template.repository')->random();

        $response = $this->getJson(url("api/templates/designer/{$template->id}"));

        $response->assertOk()
            ->assertJsonStructure([
                'first_page',
                'data_pages',
                'last_page',
                'payment_schedule'
            ]);
    }

    protected function makeGenericTemplateAttributes(): array
    {
        return [
            'name' => Str::random(20),
            'countries' => app('country.repository')->all()->random(4)->pluck('id')->toArray(),
            'company_id' => app('company.repository')->random()->id,
            'vendor_id' => app('vendor.repository')->random()->id,
            'currency_id' => app('currency.repository')->all()->random()->id,
            'form_data' => []
        ];
    }
}
