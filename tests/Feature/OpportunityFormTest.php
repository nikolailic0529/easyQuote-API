<?php

namespace Tests\Feature;

use App\Domain\Pipeline\Models\Pipeline;
use App\Domain\Worldwide\Models\OpportunityForm;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group opportunity
 * @group build
 */
class OpportunityFormTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to view paginated opportunity form entities.
     */
    public function testCanViewPaginatedOpportunityForms(): void
    {
        $this->authenticateApi();

        $opportunityForm = factory(OpportunityForm::class)->create();

        $this->getJson('api/opportunity-forms')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'space_name',
                        'pipeline_name',
                        'created_at',
                        'updated_at',
                        'is_system',
                        'permissions' => [
                            'view',
                            'update',
                            'delete',
                        ],
                    ],
                ],
                'links' => [
                    'first', 'last', 'prev', 'next',
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);

        $this->getJson('api/opportunity-forms?order_by_space_name=desc')->assertOk();
        $this->getJson('api/opportunity-forms?order_by_pipeline_name=desc')->assertOk();
        $this->getJson('api/opportunity-forms?order_by_created_at=desc')->assertOk();
        $this->getJson('api/opportunity-forms?order_by_updated_at=desc')->assertOk();
    }

    /**
     * Test an ability to view an existing opportunity form.
     */
    public function testCanViewExistingOpportunityForm(): void
    {
        $this->authenticateApi();

        $opportunityForm = factory(OpportunityForm::class)->create();

        $this->getJson('api/opportunity-forms/'.$opportunityForm->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'pipeline_id',
                'pipeline' => [
                    'id',
                    'space_id',
                    'is_system',
                    'is_default',
                    'pipeline_name',
                    'created_at',
                    'updated_at',
                ],
                'form_data',
                'sidebar_0',
                'is_system',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * Test an ability to copy an existing opportunity form.
     */
    public function testCanCopyOpportunityForm(): void
    {
        $this->authenticateApi();

        $form = factory(OpportunityForm::class)->create([
            'is_system' => true,
        ]);

        $pipeline = factory(Pipeline::class)->create();

        $r = $this->postJson('api/opportunity-forms/'.$form->getKey().'/copy', [
            'pipeline_id' => $pipeline->getKey(),
        ])
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'space_id',
                'pipeline_id',
                'pipeline' => [
                    'id',
                    'pipeline_name',
                ],
                'form_schema_id',
                'form_data',
                'is_system',
                'created_at',
                'updated_at',
            ]);

        $this->assertNotSame($r->json('id'), $form->getKey());
        $this->assertNotSame($r->json('form_schema_id'), $form->formSchema()->getParentKey());
    }

    /**
     * Test an ability to create a new opportunity form entity.
     */
    public function testCanCreateNewOpportunityForm(): void
    {
        $this->authenticateApi();

        $pipeline = factory(Pipeline::class)->create();

        $this->postJson('api/opportunity-forms', [
            'pipeline_id' => $pipeline->getKey(),
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'pipeline_id',
                'pipeline' => [
                    'id',
                    'space_id',
                    'is_system',
                    'is_default',
                    'pipeline_name',
                    'created_at',
                    'updated_at',
                ],
                'form_data',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * Test an ability to update an existing opportunity form entity.
     */
    public function testCanUpdateExistingOpportunityForm(): void
    {
        $this->authenticateApi();

        $opportunityForm = factory(OpportunityForm::class)->create();

        $pipeline = factory(Pipeline::class)->create();

        $this->patchJson('api/opportunity-forms/'.$opportunityForm->getKey(), [
            'pipeline_id' => $pipeline->getKey(),
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'pipeline_id',
                'pipeline' => [
                    'id',
                    'space_id',
                    'is_system',
                    'is_default',
                    'pipeline_name',
                    'created_at',
                    'updated_at',
                ],
                'form_data',
                'created_at',
                'updated_at',
            ]);

        $this->getJson('api/opportunity-forms/'.$opportunityForm->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'pipeline_id',
                'pipeline' => [
                    'id',
                    'space_id',
                    'is_system',
                    'is_default',
                    'pipeline_name',
                    'created_at',
                    'updated_at',
                ],
                'form_data',
                'created_at',
                'updated_at',
            ])
            ->assertJsonFragment([
                'pipeline_id' => $pipeline->getKey(),
            ]);
    }

    /**
     * Test an ability to update an existing system defined opportunity form.
     */
    public function testCanNotUpdateExistingSystemDefinedOpportunityForm(): void
    {
        $pipeline = factory(Pipeline::class)->create();

        $opportunityForm = factory(OpportunityForm::class)->create(['is_system' => true]);

        $this->authenticateApi();

        $this->patchJson('api/opportunity-forms/'.$opportunityForm->getKey(), [
            'pipeline_id' => $pipeline->getKey(),
        ])
            ->assertForbidden();
    }

    /**
     * Test an ability to update a schema of an existing opportunity form entity.
     */
    public function testCanUpdateSchemaOfExistingOpportunityForm(): void
    {
        $opportunityForm = factory(OpportunityForm::class)->create();

        $this->authenticateApi();

        $this->patchJson('api/opportunity-forms/'.$opportunityForm->getKey().'/schema', [
            'form_data' => $formData = [
                [
                    'id' => Str::uuid()->toString(),
                ],
            ],
            'sidebar_0' => $sidebar0 = [
                [
                    'id' => Str::uuid()->toString(),
                ],
            ],
        ])
            ->assertNoContent();

        $this->getJson('api/opportunity-forms/'.$opportunityForm->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'pipeline_id',
                'pipeline' => [
                    'id',
                    'space_id',
                    'is_system',
                    'is_default',
                    'pipeline_name',
                    'created_at',
                    'updated_at',
                ],
                'form_data',
                'sidebar_0',
                'created_at',
                'updated_at',
            ])
            ->assertJsonFragment([
                'form_data' => $formData,
                'sidebar_0' => $sidebar0,
            ]);
    }

    /**
     * Test an ability to delete an existing opportunity form entity.
     */
    public function testCanDeleteExistingOpportunityForm(): void
    {
        $opportunityForm = factory(OpportunityForm::class)->create();

        $this->authenticateApi();

        $this->deleteJson('api/opportunity-forms/'.$opportunityForm->getKey())
            ->assertNoContent();

        $this->getJson('api/opportunity-forms/'.$opportunityForm->getKey())
            ->assertNotFound();
    }

    /**
     * Test an ability to delete an existing system defined opportunity form.
     */
    public function testCanNotDeleteExistingSystemDefinedOpportunityForm(): void
    {
        $pipeline = factory(Pipeline::class)->create();

        $opportunityForm = factory(OpportunityForm::class)->create(['is_system' => true]);

        $this->authenticateApi();

        $this->deleteJson('api/opportunity-forms/'.$opportunityForm->getKey(), [
            'pipeline_id' => $pipeline->getKey(),
        ])
            ->assertForbidden();
    }
}
