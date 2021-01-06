<?php

namespace Tests\Unit\Template;

use Tests\TestCase;
use Tests\Unit\Traits\{
    WithFakeUser,
    AssertsListing
};
use App\Models\Template\QuoteTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\{Str, Arr};

/**
 * @group build
 */
class QuoteTemplateTest extends TestCase
{
    use WithFakeUser, AssertsListing, DatabaseTransactions;

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

        $this->getJson(url("api/templates?{$query}"))->assertOk();
    }

    /**
     * Test Template creating with valid attributes.
     *
     * @return void
     */
    public function testTemplateCreating()
    {
        $attributes = $this->makeGenericTemplateAttributes();

        $this->postJson(url("api/templates"), $attributes)
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
        $template = app('template.repository')->create($this->makeGenericTemplateAttributes());

        $attributes = $this->makeGenericTemplateAttributes();

        $this->patchJson(url("api/templates/{$template->id}"), $attributes)
            ->assertOk()
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
        $template = app('template.repository')->random(1, fn (Builder $query) => $query->system());

        if ($template === null) {
            $template = factory(QuoteTemplate::class)->create(['is_system' => true]);
        }

        $attributes = $this->makeGenericTemplateAttributes();

        $this->patchJson(url("api/templates/{$template->id}"), $attributes)
            ->assertForbidden()
            ->assertJsonFragment(['message' => QTSU_01]);
    }

    /**
     * Test copying an existing Master Template.
     *
     * @return void
     */
    public function testTemplateCopying()
    {
        $template = app('template.repository')->random(1, fn (Builder $query) => $query->system());

        if ($template === null) {
            $template = factory(QuoteTemplate::class)->create(['is_system' => true]);
        }

        $response = $this->putJson(url("api/templates/copy/{$template->id}"), [])->assertOk();

        /**
         * Test that a newly copied Template existing.
         */
        $id = $response->json('id');

        $this->getJson(url("api/templates/{$id}"))
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
        $template = app('template.repository')->create($this->makeGenericTemplateAttributes());

        $this->deleteJson(url("api/templates/{$template->id}"), [])
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertSoftDeleted($template);
    }

    /**
     * Test deleting a Master Template. Deleting Master Template is forbidden.
     *
     * @return void
     */
    public function testMasterTemplateDeleting()
    {
        $template = app('template.repository')->random(1, fn (Builder $query) => $query->system());

        if ($template === null) {
            $template = factory(QuoteTemplate::class)->create(['is_system' => true]);
        }

        $this->deleteJson(url("api/templates/{$template->id}"), [])
            ->assertForbidden()
            ->assertJsonFragment(['message' => QTSD_01]);
    }

    /**
     * Test activating a newly created Template.
     *
     * @return void
     */
    public function testTemplateActivating()
    {
        $template = tap(app('template.repository')->create($this->makeGenericTemplateAttributes()))->deactivate();

        $this->putJson(url("api/templates/activate/{$template->id}"), [])
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertNotNull($template->refresh()->activated_at);
    }

    /**
     * Test deactivating a newly created Template.
     *
     * @return void
     */
    public function testTemplateDeactivating()
    {
        $template = tap(app('template.repository')->create($this->makeGenericTemplateAttributes()))->activate();

        $this->putJson(url("api/templates/deactivate/{$template->id}"), [])
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
        $template = app('template.repository')->random();

        $this->getJson(url("api/templates/designer/{$template->id}"))
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
            'form_data' => []
        ];
    }
}
