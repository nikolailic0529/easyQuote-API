<?php

namespace Tests\Feature;

use App\Models\Data\Country;
use App\Models\Template\HpeContractTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Unit\Traits\AssertsListing;
use Tests\Unit\Traits\WithFakeUser;

/**
 * @group build
 */
class HpeContractTemplateTest extends TestCase
{
    use WithFakeUser, AssertsListing, DatabaseTransactions;

    protected static array $assertableAttributes = ['id', 'name', 'company_id', 'vendor_id', 'currency_id', 'form_data', 'data_headers', 'countries'];

    /**
     * Test an ability to view paginated hpe contract templates.
     *
     * @return void
     */
    public function testCanViewPaginatedHpeContractTemplates()
    {
        $this->getJson(url("api/hpe-contract-templates"))->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'is_system',
                        'company_id',
                        'vendor_id',
                        'currency_id',
                        'company' => [
                            'id', 'name',
                        ],
                        'vendor' => [
                            'id', 'name',
                        ],
                        'countries' => [
                            '*' => [
                                'id', 'name',
                            ],
                        ],
                        'permissions' => [
                            'view', 'update', 'delete',
                        ],
                        'created_at',
                        'activated_at',
                    ],
                ],
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);

        $query = http_build_query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_name' => 'asc',
            'order_by_company_name' => 'asc',
            'order_by_vendor_name' => 'asc',
        ]);

        $this->getJson("api/contract-templates?$query")->assertOk();
    }

    /**
     * Test an ability to create hpe contract template.
     *
     * @return void
     */
    public function testCanCreateHpeContractTemplate()
    {
        $attributes = factory(HpeContractTemplate::class)->raw([
            'countries' => Country::limit(2)->pluck('id')->all()
        ]);

        $response = $this->postJson('api/hpe-contract-templates', $attributes)
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'name',
                'company_id',
                'vendor_id',
                'currency_id',
                'form_data',
                'data_headers',
                'countries',
                'activated_at',
            ]);

        $this->assertNotEmpty($response->json('activated_at'));
    }

    /**
     * Test an ability to update an existing hpe contract template.
     *
     * @return void
     */
    public function testCanUpdateHpeContractTemplate()
    {
        $template = factory(HpeContractTemplate::class)->create();

        $attributes = factory(HpeContractTemplate::class)->raw();

        $this->patchJson('api/hpe-contract-templates/'.$template->getKey(), $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'company_id',
                'vendor_id',
                'currency_id',
                'form_data',
                'data_headers',
                'countries',
                'activated_at',
            ]);
    }

    /**
     * Test an ability to activate an existing hpe contract template.
     *
     * @return void
     */
    public function testCanActivateHpeContractTemplate()
    {
        $template = factory(HpeContractTemplate::class)->create();
        $template->activated_at = null;
        $template->save();

        $this->putJson('api/hpe-contract-templates/activate/'.$template->getKey())->assertOk();

        $response = $this->getJson('api/hpe-contract-templates/'.$template->getKey())->assertOk()
                ->assertJsonStructure([
                    'id', 'activated_at'
                ]);
        $this->assertNotEmpty($response->json('activated_at'));
    }

    /**
     * Test an ability to deactivate an existing hpe contract template.
     *
     * @return void
     */
    public function testCanDeactivateHpeContractTemplate()
    {
        $template = factory(HpeContractTemplate::class)->create();
        $template->activated_at = now();
        $template->save();

        $this->putJson('api/hpe-contract-templates/deactivate/'.$template->getKey())->assertOk();

        $response = $this->getJson('api/hpe-contract-templates/'.$template->getKey())->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at'
            ]);
        $this->assertNull($response->json('activated_at'));
    }

    /**
     * Test an ability to delete an existing hpe contract template.
     *
     * @return void
     */
    public function testCanDeleteHpeContractTemplate()
    {
        $template = factory(HpeContractTemplate::class)->create();

        $this->deleteJson('api/hpe-contract-templates/'.$template->getKey())->dump()->assertOk();

        $this->getJson('api/hpe-contract-templates/'.$template->getKey())->assertNotFound();
    }
}
