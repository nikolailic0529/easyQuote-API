<?php

namespace Tests\Unit\Template;

use App\Models\QuoteTemplate\HpeContractTemplate;
use Tests\TestCase;
use Tests\Unit\Traits\AssertsListing;
use Tests\Unit\Traits\WithFakeUser;
use Illuminate\Support\Str;

class HpeContractTemplateTest extends TestCase
{
    use WithFakeUser, AssertsListing;

    protected static array $assertableAttributes = ['id', 'name', 'company_id', 'vendor_id', 'currency_id', 'form_data', 'data_headers', 'countries'];

    /**
     * Test HPE Contract Templates listing.
     *
     * @return void
     */
    public function testHpeContractTemplateListing()
    {
        $response = $this->getJson(url("api/hpe-contract-templates"));

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
     * Test creating a new HPE Contract Template.
     *
     * @return void
     */
    public function testHpeContractTemplateCreating()
    {
        $attributes = factory(HpeContractTemplate::class)->raw();

        $this->postJson('api/hpe-contract-templates', $attributes)
            ->assertCreated()
            ->assertJsonStructure(static::$assertableAttributes);
    }

    /**
     * Test updating a newly created HPE Contract Template.
     *
     * @return void
     */
    public function testHpeContractTemplateUpdating()
    {
        $template = transform(factory(HpeContractTemplate::class)->raw(), function ($attributes) {
            return app('hpe_contract_template.repository')->create($attributes);
        });

        $attributes = factory(HpeContractTemplate::class)->raw();

        $response = $this->patchJson('api/hpe-contract-templates/' . $template->getKey(), $attributes)
            ->assertOk()
            ->assertJsonStructure(static::$assertableAttributes);

        $template->refresh();

        $this->assertEquals($template->name, $attributes['name']);

        $this->assertEquals($template->countries->pluck('id')->sort()->values()->toArray(), $attributes['countries']);

        $this->assertEquals($template->company_id, $attributes['company_id']);

        $this->assertEquals($template->vendor_id, $attributes['vendor_id']);

        $this->assertEquals($template->currency_id, $attributes['currency_id']);

        $this->assertEquals($template->form_data, $attributes['form_data']);
    }

    /**
     * Test activating a newly created HPE Contract Template.
     *
     * @return void
     */
    public function testHpeContractTemplateActivating()
    {
        $template = transform(factory(HpeContractTemplate::class)->raw(), function ($attributes) {
            $template = app('hpe_contract_template.repository')->create($attributes);
            return tap($template)->deactivate();
        });

        $this->putJson('api/hpe-contract-templates/activate/'.$template->getKey())->assertOk();

        $this->assertNotNull($template->refresh()->activated_at);
    }

    /**
     * Test deactivating a newly created HPE Contract Template.
     *
     * @return void
     */
    public function testHpeContractTemplateDeactivating()
    {
        $template = transform(factory(HpeContractTemplate::class)->raw(), function ($attributes) {
            $template = app('hpe_contract_template.repository')->create($attributes);
            return tap($template)->activate();
        });

        $this->putJson('api/hpe-contract-templates/deactivate/'.$template->getKey())->assertOk();

        $this->assertNull($template->refresh()->activated_at);
    }

    /**
     * Test deleting a newly created HPE Contract Template.
     *
     * @return void
     */
    public function testHpeContractTemplateDeleting()
    {
        $template = transform(factory(HpeContractTemplate::class)->raw(), function ($attributes) {
            return app('hpe_contract_template.repository')->create($attributes);
        });

        $this->deleteJson('api/hpe-contract-templates/'.$template->getKey())->assertOk();

        $this->assertSoftDeleted($template);
    }
}
