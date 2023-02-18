<?php

namespace Tests\Feature;

use App\Domain\Authentication\Services\UserTeamGate;
use App\Domain\Authorization\Models\Role;
use App\Domain\Company\Models\Company;
use App\Domain\Country\Models\Country;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\Team\Models\Team;
use App\Domain\User\Models\User;
use App\Domain\Vendor\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group build
 */
class QuoteTemplateTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to view paginated templates listing.
     *
     * @return void
     */
    public function testCanViewPaginatedTemplatesListing()
    {
        $this->authenticateApi();

        QuoteTemplate::query()->delete();

        factory(QuoteTemplate::class, 30)->create()
            ->each(function (QuoteTemplate $quoteTemplate) {
                $quoteTemplate->vendors()->sync(Vendor::all());
                $quoteTemplate->countries()->sync(Country::limit(2)->get());
            });

        $this->getJson('api/templates')
            ->assertOk()
            ->assertJsonStructure([
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'to',
                    'per_page',
                    'total',
                ],
                'links' => [
                    'first',
                    'last',
                    'next',
                    'prev',
                ],
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'is_system',
                        'company_id',
                        'vendor_id',
                        'currency_id',
                        'company' => ['id', 'name'],
                        'country_names',
                        'vendor_names',
                        'permissions' => ['view', 'update', 'delete'],
                        'activated_at',
                    ],
                ],
            ]);

        $query = http_build_query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_name' => 'asc',
            'order_by_company_name' => 'asc',
            'order_by_vendor_name' => 'asc',
        ]);

        $this->getJson("api/templates?$query")->assertOk();
    }

    /**
     * Test an ability to view an existing quote template.
     */
    public function testCanViewTemplate()
    {
        $this->authenticateApi();

        $quoteTemplate = factory(QuoteTemplate::class)->create();

        $this->getJson('api/templates/'.$quoteTemplate->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'business_division_id',
                'contract_type_id',
                'name',
                'company_id',
                'vendors' => [
                    '*' => [
                        'id',
                        'name',
                        'short_code',
                    ],
                ],
                'countries' => [
                    '*' => [
                        'id', 'name',
                    ],
                ],
                'currency_id',
                'form_data',
                'created_at',
            ]);
    }

    /**
     * Test an ability to create a new quote template.
     *
     * @return void
     */
    public function testCanCreateTemplate()
    {
        $this->authenticateApi();

        $attributes = factory(QuoteTemplate::class)->raw([
            'countries' => Country::limit(2)->pluck('id')->all(),
            'vendors' => Vendor::limit(2)->pluck('id')->all(),
        ]);

        $this->postJson(url('api/templates'), $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'business_division_id',
                'contract_type_id',
                'name',
                'company_id',
                'vendors' => [
                    '*' => [
                        'id',
                        'name',
                        'short_code',
                    ],
                ],
                'countries' => [
                    '*' => [
                        'id', 'name',
                    ],
                ],
                'currency_id',
                'form_data',
                'created_at',
            ]);
    }

    /**
     * Test an ability to filter rescue templates by multiple vendors.
     *
     * @return void
     */
    public function testCanFilterRescueTemplatesByMultipleVendors()
    {
        $this->authenticateApi();

        $templates = factory(QuoteTemplate::class, 10)->create();

        $this->postJson('api/templates/filter', [
            'company_id' => $templates->random()->company_id,
            'vendors' => [$templates->random()->vendor_id],
        ])
            ->assertOk();
    }

    /**
     * Test an ability to filter worldwide quote templates by multiple vendors.
     *
     * @return void
     */
    public function testCanFilterWorldwideTemplatesByMultipleVendors()
    {
        $this->authenticateApi();

        $templates = factory(QuoteTemplate::class, 10)->create();

        $this->postJson('api/templates/filter-ww', [
            'company_id' => $templates->random()->company_id,
            'vendors' => [$templates->random()->vendor_id],
        ])
            ->assertOk();
    }

    /**
     * Test an ability to filter worldwide pack quote templates by the company.
     *
     * @return void
     */
    public function testCanFilterWorldwidePackTemplatesByCompany()
    {
        $this->authenticateApi();

        $templates = factory(QuoteTemplate::class, 10)->create([
            'business_division_id' => BD_WORLDWIDE,
            'contract_type_id' => CT_PACK,
        ]);

        $this->postJson('api/templates/filter-ww/pack', [
            'company_id' => $templates->random()->company_id,
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'name',
                ],
            ]);
    }

    /**
     * Test an ability to filter worldwide pack quote templates by multiple vendors.
     *
     * @return void
     */
    public function testCanFilterWorldwideContractTemplatesByMultipleVendors()
    {
        $this->authenticateApi();

        $templates = factory(QuoteTemplate::class, 10)->create([
            'business_division_id' => BD_WORLDWIDE,
            'contract_type_id' => CT_CONTRACT,
        ]);

        $vendors = Vendor::query()->where('is_system', true)->get();

        $templates->each(function (QuoteTemplate $template) use ($vendors) {
            $template->vendors()->sync($vendors);
        });

        $this->postJson('api/templates/filter-ww/contract', [
            'company_id' => $templates->random()->company->getKey(),
            'vendors' => $vendors->modelKeys(),
        ])
//            ->dump()
            ->assertOk();
    }

    /**
     * Test an ability to filter rescue quote templates by specified company.
     *
     * @return void
     */
    public function testCanRescueFilterTemplatesByCompany()
    {
        $this->authenticateApi();

        $templates = factory(QuoteTemplate::class, 10)->create();

        $this->postJson('api/quotes/step/1', [
            'company_id' => Company::value('id'),
        ])
//            ->dump()
            ->assertOk();
    }

    /**
     * Test an ability to update an existing quote template.
     *
     * @return void
     */
    public function testCanUpdateQuoteTemplate()
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create(['form_data' => ['TEMPLATE_SCHEMA']]);

        $attributes = factory(QuoteTemplate::class)->raw([
            'countries' => Country::limit(2)->pluck('id')->all(),
            'form_values_data' => ['template_schema'],
            'vendors' => Vendor::limit(2)->pluck('id')->all(),
        ]);

        $this->getJson('api/templates/'.$template->getKey())
            ->assertOk()
            ->assertJsonFragment(['form_data' => ['TEMPLATE_SCHEMA']]);

        $this->patchJson('api/templates/'.$template->getKey(), Arr::except($attributes, ['form_data', 'form_values_data']))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure(['id', 'business_division_id', 'contract_type_id', 'name', 'company_id', 'vendor_id', 'currency_id', 'form_data', 'created_at']);

        $this->getJson('api/templates/'.$template->getKey())
            ->assertOk()
            ->assertJsonFragment(['form_data' => ['TEMPLATE_SCHEMA']]);

        $this->patchJson('api/templates/'.$template->getKey(), Arr::only($attributes, ['form_data', 'form_values_data']))
            ->assertOk()
            ->assertJsonStructure(['id', 'business_division_id', 'contract_type_id', 'name', 'company_id', 'vendor_id', 'currency_id', 'form_data', 'created_at']);

        $response = $this->getJson('api/templates/'.$template->getKey())
            ->assertOk();

        $this->assertNotEquals(['TEMPLATE_SCHEMA'], $response->json('form_data'));
    }

    /**
     * Test an ability to update a master quote template.
     *
     * @return void
     */
    public function testCanNotUpdateMasterTemplate()
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create(['is_system' => true]);

        $attributes = factory(QuoteTemplate::class)->raw([
            'countries' => Country::limit(2)->pluck('id')->all(),
        ]);

        $this->patchJson(url('api/templates/'.$template->getKey()), $attributes)
            ->assertForbidden()
            ->assertJsonFragment(['message' => QTSU_01]);
    }

    /**
     * Test an ability to make a copy of an existing template.
     *
     * @return void
     */
    public function testCanCopyTemplate()
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create(['is_system' => true]);

        $response = $this->putJson('api/templates/copy/'.$template->getKey(), [])->assertOk();

        /**
         * Assert the newly replicated template exists.
         */
        $id = $response->json('id');

        $this->getJson("api/templates/$id")
            ->assertOk()
            ->assertJsonStructure(['id', 'business_division_id', 'contract_type_id', 'name', 'company_id', 'vendor_id', 'currency_id', 'form_data', 'created_at']);
    }

    /**
     * Test an ability to delete an existing template.
     *
     * @return void
     */
    public function testCanDeleteTemplate()
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create();

        $this->deleteJson('api/templates/'.$template->getKey(), [])
            ->assertNoContent();

        $this->getJson('api/templates/'.$template->getKey())
            ->assertNotFound();
    }

    /**
     * Test an ability to delete master template.
     *
     * @return void
     */
    public function testCanNotDeleteMasterTemplate()
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create(['is_system' => true]);

        $this->deleteJson('api/templates/'.$template->getKey(), [])
            ->assertForbidden()
            ->assertJsonFragment(['message' => QTSD_01]);
    }

    /**
     * Test an ability to activate an existing quote template.
     *
     * @return void
     */
    public function testCanActivateTemplate()
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create(['activated_at' => null]);

        $this->putJson('api/templates/activate/'.$template->getKey(), [])
            ->assertNoContent();

        $response = $this->getJson('api/templates/'.$template->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertNotNull($response->json('activated_at'));
    }

    /**
     * Test an ability to deactivate an existing quote template.
     *
     * @return void
     */
    public function testCanDeactivateTemplate()
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create(['activated_at' => now()]);

        $this->putJson('api/templates/deactivate/'.$template->getKey(), [])
            ->assertNoContent();

        $response = $this->getJson('api/templates/'.$template->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertNotNull($response->json('activated_at'));
    }

    /**
     * Test an ability to view template schema.
     *
     * @return void
     */
    public function testCanViewTemplateSchema()
    {
        $this->authenticateApi();

        $template = factory(QuoteTemplate::class)->create([
            'business_division_id' => BD_WORLDWIDE,
        ]);

        $template->vendors()->sync(Vendor::limit(2)->get());

        $this->getJson('api/templates/designer/'.$template->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'first_page',
                'data_pages',
                'last_page',
                'payment_schedule',
            ]);
    }

    /**
     * Test an ability to update an existing quote template when the actor is the team leader of the template owner.
     */
    public function testCanUpdateQuoteTemplateOwnedByLedTeamUser(): void
    {
        $this->authenticateApi();

        /** @var \App\Domain\Authorization\Models\Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions(['view_own_quote_templates', 'update_own_quote_templates']);

        /** @var Team $team */
        $team = factory(Team::class)->create();

        /** @var \App\Domain\User\Models\User $teamLeader */
        $teamLeader = User::factory()->create(['team_id' => $team->getKey()]);
        $teamLeader->syncRoles($role);

        /** @var \App\Domain\User\Models\User $ledUser */
        $ledUser = User::factory()->create(['team_id' => $team->getKey()]);
        $ledUser->syncRoles($role);

        /** @var \App\Domain\Rescue\Models\QuoteTemplate $template */
        $template = factory(QuoteTemplate::class)->create(['user_id' => $ledUser->getKey()]);

        $data = [
            'name' => Str::random(40),
        ];

        $this->authenticateApi($teamLeader);

        $this->patchJson('api/templates/'.$template->getKey(), $data)
//            ->dump()
            ->assertForbidden();

        $team->teamLeaders()->sync($teamLeader);

        $this->app->forgetInstance(UserTeamGate::class);

        $this->authenticateApi($teamLeader);

        $this->patchJson('api/templates/'.$template->getKey(), $data)
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/templates/'.$template->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
            ]);

        $this->assertSame($data['name'], $response->json('name'));
    }

    /**
     * Test an ability to delete an existing quote template owned when the actor is the team leader of the template owner.
     */
    public function testCanDeleteQuoteTemplateOwnedByLedTeamUser(): void
    {
        $this->authenticateApi();

        /** @var \App\Domain\Authorization\Models\Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions(['view_own_quote_templates', 'create_quote_templates', 'update_own_quote_templates', 'delete_own_quote_templates']);

        /** @var Team $team */
        $team = factory(Team::class)->create();

        /** @var \App\Domain\User\Models\User $teamLeader */
        $teamLeader = User::factory()->create(['team_id' => $team->getKey()]);
        $teamLeader->syncRoles($role);

        /** @var \App\Domain\User\Models\User $ledUser */
        $ledUser = User::factory()->create(['team_id' => $team->getKey()]);
        $ledUser->syncRoles($role);

        /** @var \App\Domain\Rescue\Models\QuoteTemplate $template */
        $template = factory(QuoteTemplate::class)->create(['user_id' => $ledUser->getKey()]);

        $this->authenticateApi($teamLeader);

        $this->deleteJson('api/templates/'.$template->getKey())
//            ->dump()
            ->assertForbidden();

        $team->teamLeaders()->sync($teamLeader);

        $this->app->forgetInstance(UserTeamGate::class);

        $this->authenticateApi($teamLeader);

        $this->deleteJson('api/templates/'.$template->getKey())
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/templates/'.$template->getKey())
            ->assertNotFound();
    }
}
