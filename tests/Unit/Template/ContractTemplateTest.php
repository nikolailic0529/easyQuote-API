<?php

namespace Tests\Unit\Template;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\Traits\{
    WithFakeUser,
    AssertsListing
};
use Illuminate\Support\{Str, Arr};

/**
 * @group build
 */
class ContractTemplateTest extends TestCase
{
    use WithFakeUser, AssertsListing, DatabaseTransactions;

    protected static array $assertableAttributes = ['name', 'company_id', 'vendor_id', 'currency_id', 'form_data'];

    /**
     * Test Template listing.
     *
     * @return void
     */
    public function testTemplateListing()
    {
        $response = $this->getJson(url("api/contract-templates"));

        $this->assertListing($response);

        $query = http_build_query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_name' => 'asc',
            'order_by_company_name' => 'asc',
            'order_by_vendor_name' => 'asc',
        ]);

        $this->getJson(url("api/contract-templates?{$query}"))->assertOk();
    }

    /**
     * Test Template creating with valid attributes.
     *
     * @return void
     */
    public function testTemplateCreating()
    {
        $attributes = $this->makeGenericTemplateAttributes();

        $this->postJson(url("api/contract-templates"), $attributes)
            ->assertOk()
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
        $template = app('contract_template.repository')->create($this->makeGenericTemplateAttributes());

        $attributes = $this->makeGenericTemplateAttributes();

        $this->patchJson(url("api/contract-templates/{$template->id}"), $attributes)
            ->assertOk()
            ->assertJsonStructure(static::$assertableAttributes)
            ->assertJsonFragment(Arr::only($attributes, static::$assertableAttributes));
    }

    /**
     * Test copying an existing Template.
     *
     * @return void
     */
    public function testTemplateCopying()
    {
        $template = app('contract_template.repository')->create($this->makeGenericTemplateAttributes());

        $response = $this->putJson(url("api/contract-templates/copy/{$template->id}"), [])->assertOk();

        /**
         * Test that a newly copied Template existing.
         */
        $id = $response->json('id');

        $this->getJson(url("api/contract-templates/{$id}"))
            ->assertOk()
            ->assertJsonStructure(static::$assertableAttributes);
    }

    /**
     * Test deleting a newly created Template.
     *
     * @return void
     */
    public function testTemplateDeleting()
    {
        $template = app('contract_template.repository')->create($this->makeGenericTemplateAttributes());

        $this->deleteJson(url("api/contract-templates/{$template->id}"), [])
            ->assertOk()
            ->assertExactJson([true]);
    }

    /**
     * Test activating a newly created Template.
     *
     * @return void
     */
    public function testTemplateActivating()
    {
        $template = tap(app('contract_template.repository')->create($this->makeGenericTemplateAttributes()))->deactivate();

        $this->putJson(url("api/contract-templates/activate/{$template->id}"), [])
            ->assertOk()->assertExactJson([true]);

        $this->assertNotNull($template->refresh()->activated_at);
    }

    /**
     * Test deactivating a newly created Template.
     *
     * @return void
     */
    public function testTemplateDeactivating()
    {
        $template = tap(app('contract_template.repository')->create($this->makeGenericTemplateAttributes()))->activate();

        $this->putJson(url("api/contract-templates/deactivate/{$template->id}"), [])
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertNull($template->refresh()->activated_at);
    }

    /**
     * Test designer data for a specified Template.
     *
     * @return void
     */
    public function testTemplateDesigner()
    {
        $template = app('contract_template.repository')->create($this->makeGenericTemplateAttributes());

        $this->getJson(url("api/contract-templates/designer/{$template->id}"))
            ->assertOk()
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
            'form_data' => [],
            'is_system' => false,
        ];
    }
}
