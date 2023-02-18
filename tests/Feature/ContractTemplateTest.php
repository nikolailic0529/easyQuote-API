<?php

namespace Tests\Feature;

use App\Domain\Authentication\Services\UserTeamGate;
use App\Domain\Authorization\Models\Role;
use App\Domain\Company\Models\Company;
use App\Domain\Country\Models\Country;
use App\Domain\Rescue\Models\ContractTemplate;
use App\Domain\Team\Models\Team;
use App\Domain\User\Models\User;
use App\Domain\Vendor\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\{Str};
use Tests\TestCase;

/**
 * @group build
 */
class ContractTemplateTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test Template listing.
     *
     * @return void
     */
    public function testTemplateListing()
    {
        $this->authenticateApi();

        $this->getJson('api/contract-templates')
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

        $company = Company::factory()->create();
        $vendor = factory(Vendor::class)->create();
        $country = Country::query()->where('iso_3166_2', 'GB')->first();

        /** @var ContractTemplate $contactTemplate */
        $contactTemplate = factory(ContractTemplate::class)->create([
            'company_id' => $company->getKey(),
            'vendor_id' => $vendor->getKey(),
            'business_division_id' => BD_WORLDWIDE,
            'contract_type_id' => CT_CONTRACT,
        ]);

        $contactTemplate->countries()->sync($country);

        $response = $this->postJson('api/contract-templates/filter-ww/contract', [
            'company_id' => $company->getKey(),
            'vendor_id' => $vendor->getKey(),
            'country_id' => $country->getKey(),
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'name',
                ],
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

        $company = Company::factory()->create();
        $vendor = factory(Vendor::class)->create();
        $country = Country::query()->where('iso_3166_2', 'GB')->first();

        /** @var \App\Domain\Rescue\Models\ContractTemplate $contactTemplate */
        $contactTemplate = factory(ContractTemplate::class)->create([
            'company_id' => $company->getKey(),
            'vendor_id' => $vendor->getKey(),
            'business_division_id' => BD_WORLDWIDE,
            'contract_type_id' => CT_PACK,
        ]);

        $contactTemplate->countries()->sync($country);

        $response = $this->postJson('api/contract-templates/filter-ww/pack', [
            'company_id' => $company->getKey(),
            'vendor_id' => $vendor->getKey(),
            'country_id' => $country->getKey(),
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'name',
                ],
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
        $this->authenticateApi();

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
                    'id', 'name',
                ],
                'vendor' => [
                    'id', 'name',
                ],
                'currency',
                'form_data',
                'data_headers',
                'data_headers_keyed',
                'countries' => [
                    '*' => ['id', 'name'],
                ],
                'created_at',
                'activated_at',
            ]);
    }

    /**
     * Test an ability to create a new contract template.
     *
     * @return void
     */
    public function testCanCreateContractTemplate()
    {
        $this->authenticateApi();

        $attributes = factory(ContractTemplate::class)->raw([
            'countries' => Country::limit(2)->pluck('id')->all(),
            'name' => Str::random(40),
        ]);

        $this->postJson(url('api/contract-templates'), $attributes)
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
        $this->authenticateApi();

        $template = factory(ContractTemplate::class)->create();

        $attributes = factory(ContractTemplate::class)->raw([
            'countries' => Country::limit(2)->pluck('id')->all(),
            'name' => Str::random(40),
            'form_values_data' => ['TEMPLATE_SCHEMA'],
        ]);

        $this->patchJson('api/contract-templates/'.$template->getKey(), $attributes)
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
        $this->authenticateApi();

        $template = factory(ContractTemplate::class)->create();

        $response = $this->putJson('api/contract-templates/copy/'.$template->getKey(), [])->assertOk();

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
        $this->authenticateApi();

        $template = factory(ContractTemplate::class)->create();

        $this->deleteJson('api/contract-templates/'.$template->getKey(), [])
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
        $this->authenticateApi();

        $template = factory(ContractTemplate::class)->create();
        $template->activated_at = null;
        $template->save();

        $this->putJson('api/contract-templates/activate/'.$template->getKey(), [])
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
        $this->authenticateApi();

        $template = factory(ContractTemplate::class)->create();
        $template->activated_at = now();
        $template->save();

        $this->putJson('api/contract-templates/deactivate/'.$template->getKey(), [])
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
            'type' => 'contract',
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
        $this->authenticateApi();

        $template = factory(ContractTemplate::class)->create();

        $this->getJson('api/contract-templates/designer/'.$template->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'first_page',
                'data_pages',
                'last_page',
                'payment_schedule',
            ]);
    }

    /**
     * Test an ability to update an existing contract template when the actor is the team leader of the template owner.
     */
    public function testCanUpdateContractTemplateOwnedByLedTeamUser(): void
    {
        /** @var \App\Domain\Authorization\Models\Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions(['view_own_contract_templates', 'create_contract_templates', 'update_own_contract_templates']);

        /** @var Team $team */
        $team = factory(Team::class)->create();

        /** @var \App\Domain\User\Models\User $teamLeader */
        $teamLeader = User::factory()->create(['team_id' => $team->getKey()]);
        $teamLeader->syncRoles($role);

        /** @var User $ledUser */
        $ledUser = User::factory()->create(['team_id' => $team->getKey()]);
        $ledUser->syncRoles($role);

        /** @var \App\Domain\Rescue\Models\QuoteTemplate $template */
        $template = factory(ContractTemplate::class)->create(['user_id' => $ledUser->getKey()]);

        $data = [
            'name' => Str::random(40),
        ];

        $this->authenticateApi($teamLeader);

        $this->patchJson('api/contract-templates/'.$template->getKey(), $data)
//            ->dump()
            ->assertForbidden();

        $team->teamLeaders()->sync($teamLeader);

        $this->app->forgetInstance(UserTeamGate::class);

        $this->authenticateApi($teamLeader);

        $this->patchJson('api/contract-templates/'.$template->getKey(), $data)
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/contract-templates/'.$template->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
            ]);

        $this->assertSame($data['name'], $response->json('name'));
    }

    /**
     * Test an ability to delete an existing contract template owned when the actor is the team leader of the template owner.
     */
    public function testCanDeleteContractTemplateOwnedByLedTeamUser(): void
    {
        /** @var \App\Domain\Authorization\Models\Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions(['view_own_contract_templates', 'create_contract_templates', 'update_own_contract_templates', 'delete_own_contract_templates']);

        /** @var Team $team */
        $team = factory(Team::class)->create();

        /** @var User $teamLeader */
        $teamLeader = User::factory()->create(['team_id' => $team->getKey()]);
        $teamLeader->syncRoles($role);

        /** @var \App\Domain\User\Models\User $ledUser */
        $ledUser = User::factory()->create(['team_id' => $team->getKey()]);
        $ledUser->syncRoles($role);

        /** @var \App\Domain\Rescue\Models\ContractTemplate $template */
        $template = factory(ContractTemplate::class)->create(['user_id' => $ledUser->getKey()]);

        $this->authenticateApi($teamLeader);

        $this->deleteJson('api/contract-templates/'.$template->getKey())
//            ->dump()
            ->assertForbidden();

        $team->teamLeaders()->sync($teamLeader);

        $this->app->forgetInstance(UserTeamGate::class);

        $this->authenticateApi($teamLeader);

        $this->deleteJson('api/contract-templates/'.$template->getKey())
//            ->dump()
            ->assertOk();

        $this->getJson('api/contract-templates/'.$template->getKey())
            ->assertNotFound();
    }
}
