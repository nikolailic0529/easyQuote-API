<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Data\Country;
use App\Models\Template\ContractTemplate;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\{Str};
use Tests\TestCase;
use Tests\Unit\Traits\{WithFakeUser};

/**
 * @group build
 */
class ContractTemplateTest extends TestCase
{
    use WithFakeUser, DatabaseTransactions;

    /**
     * Test Template listing.
     *
     * @return void
     */
    public function testTemplateListing()
    {
        $this->getJson("api/contract-templates")
            ->assertOk()
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

        $this->getJson(url("api/contract-templates?{$query}"))->assertOk();
    }

    /**
     * Test an ability to filter worldwide contract templates with "Services Contract" Contract Type.
     *
     * @return void
     */
    public function testCanFilterWorldwideContractServiceContractTemplates()
    {
        $this->authenticateApi();

        $company = factory(Company::class)->create();
        $vendor = factory(Vendor::class)->create();
        $country = Country::query()->where('iso_3166_2', 'GB')->first();

        /** @var ContractTemplate $contactTemplate */
        $contactTemplate = factory(ContractTemplate::class)->create([
            'company_id' => $company->getKey(),
            'vendor_id' => $vendor->getKey(),
            'business_division_id' => BD_WORLDWIDE,
            'contract_type_id' => CT_CONTRACT
        ]);

        $contactTemplate->countries()->sync($country);

        $response = $this->postJson('api/contract-templates/filter-ww/contract', [
            'company_id' => $company->getKey(),
            'vendor_id' => $vendor->getKey(),
            'country_id' => $country->getKey()
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'name'
                ]
            ]);

        $this->assertNotEmpty($response->json());
    }

    /**
     * Test an ability to filter worldwide contract templates with "Pack" Contract Type.
     *
     * @return void
     */
    public function testCanFilterWorldwidePackContractTemplates()
    {
        $this->authenticateApi();

        $company = factory(Company::class)->create();
        $vendor = factory(Vendor::class)->create();
        $country = Country::query()->where('iso_3166_2', 'GB')->first();

        /** @var ContractTemplate $contactTemplate */
        $contactTemplate = factory(ContractTemplate::class)->create([
            'company_id' => $company->getKey(),
            'vendor_id' => $vendor->getKey(),
            'business_division_id' => BD_WORLDWIDE,
            'contract_type_id' => CT_PACK
        ]);

        $contactTemplate->countries()->sync($country);

        $response = $this->postJson('api/contract-templates/filter-ww/pack', [
            'company_id' => $company->getKey(),
            'vendor_id' => $vendor->getKey(),
            'country_id' => $country->getKey()
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'name'
                ]
            ]);

        $this->assertNotEmpty($response->json());
    }

    /**
     * Test an ability to view an existing contract template.
     *
     * @return void
     */
    public function testCanViewContractTemplate()
    {
        $contractTemplate = factory(ContractTemplate::class)->create();

        $this->getJson('api/contract-templates/'.$contractTemplate->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'is_system',
                'company_id',
                'vendor_id',
                'currency_id',
                'business_division_id',
                'contract_type_id',
                'company' => [
                    'id', 'name'
                ],
                'vendor' => [
                    'id', 'name'
                ],
                'currency',
                'form_data',
                'data_headers',
                'data_headers_keyed',
                'countries' => [
                    '*' => ['id', 'name']
                ],
                'created_at',
                'activated_at'
            ]);
    }

    /**
     * Test an ability to create a new contract template.
     *
     * @return void
     */
    public function testCanCreateContractTemplate()
    {
        $attributes = factory(ContractTemplate::class)->raw([
            'countries' => Country::limit(2)->pluck('id')->all(),
            'name' => Str::random(40),
        ]);

        $this->postJson(url("api/contract-templates"), $attributes)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'name',
                'business_division_id',
                'contract_type_id',
                'company_id',
                'vendor_id',
                'currency_id',
                'form_data',
            ]);
    }

    /**
     * Test an ability to update an existing contract template.
     *
     * @return void
     */
    public function testCanUpdateContractTemplate()
    {
        $template = factory(ContractTemplate::class)->create();

        $attributes = factory(ContractTemplate::class)->raw([
            'countries' => Country::limit(2)->pluck('id')->all(),
            'name' => Str::random(40),
            'form_values_data' => ['TEMPLATE_SCHEMA'],
        ]);

        $this->patchJson("api/contract-templates/".$template->getKey(), $attributes)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'business_division_id',
                'contract_type_id',
                'company_id',
                'vendor_id',
                'currency_id',
                'form_data',
            ]);
    }

    /**
     * Test an ability to copy an existing contract template.
     *
     * @return void
     */
    public function testCanCopyContractTemplate()
    {
        $template = factory(ContractTemplate::class)->create();

        $response = $this->putJson("api/contract-templates/copy/".$template->getKey(), [])->assertOk();

        /**
         * Test that a newly copied Template existing.
         */
        $id = $response->json('id');

        $this->getJson("api/contract-templates/$id")
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'company_id',
                'vendor_id',
                'currency_id',
                'form_data',
            ]);
    }

    /**
     * Test an ability to delete an existing contract template.
     *
     * @return void
     */
    public function testCanDeleteContractTemplate()
    {
        $template = factory(ContractTemplate::class)->create();

        $this->deleteJson("api/contract-templates/".$template->getKey(), [])
            ->assertOk()
            ->assertExactJson([true]);

        $this->getJson('api/contract-templates/'.$template->getKey())
            ->assertNotFound();
    }

    /**
     * Test an ability to activate an existing contract template.
     *
     * @return void
     */
    public function testCanActivateContractTemplate()
    {
        $template = factory(ContractTemplate::class)->create();
        $template->activated_at = null;
        $template->save();

        $this->putJson("api/contract-templates/activate/".$template->getKey(), [])
            ->assertOk();

        $response = $this->getJson('api/contract-templates/'.$template->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertNotNull($response->json('activated_at'));
    }

    /**
     * Test an ability to deactivate an existing contract template.
     *
     * @return void
     */
    public function testCanDeactivateContractTemplate()
    {
        $template = factory(ContractTemplate::class)->create();
        $template->activated_at = now();
        $template->save();

        $this->putJson("api/contract-templates/deactivate/".$template->getKey(), [])
            ->assertOk();

        $response = $this->getJson('api/contract-templates/'.$template->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertNull($response->json('activated_at'));
    }

    /**
     * Test an ability to filter contract templates by specified company.
     *
     * @return void
     */
    public function testCanFilterTemplatesByCompany()
    {
        $this->authenticateApi();

        $templates = factory(ContractTemplate::class, 10)->create();

        $this->postJson('api/quotes/step/1', [
            'company_id' => Company::value('id'),
            'type' => 'contract'
        ])
//            ->dump()
            ->assertOk();
    }

    /**
     * Test an ability to view template schema of an existing contract template.
     *
     * @return void
     */
    public function testCanViewTemplateSchema()
    {
        $template = factory(ContractTemplate::class)->create();

        $this->getJson("api/contract-templates/designer/".$template->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'first_page',
                'data_pages',
                'last_page',
                'payment_schedule',
            ]);
    }
}
