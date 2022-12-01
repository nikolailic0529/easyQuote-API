<?php

namespace Tests\Feature;

use App\Models\OpportunityForm\OpportunityForm;
use App\Models\Pipeline\Pipeline;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webpatser\Uuid\Uuid;

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
                            'delete'
                        ],
                    ]
                ],
                'links' => [
                    'first', 'last', 'prev', 'next'
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'per_page',
                    'to',
                    'total'
                ]
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
                    'updated_at'
                ],
                'form_data',
                'is_system',
                'created_at',
                'updated_at'
            ]);
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
                    'updated_at'
                ],
                'form_data',
                'created_at',
                'updated_at'
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
                    'updated_at'
                ],
                'form_data',
                'created_at',
                'updated_at'
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
                    'updated_at'
                ],
                'form_data',
                'created_at',
                'updated_at'
            ])
            ->assertJsonFragment([
                'pipeline_id' => $pipeline->getKey()
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
            'pipeline_id' => $pipeline->getKey()
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
                    'id' => (string)Uuid::generate(4)
                ]
            ]
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
                    'updated_at'
                ],
                'form_data',
                'created_at',
                'updated_at'
            ])
            ->assertJsonFragment([
                'form_data' => $formData
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
            'pipeline_id' => $pipeline->getKey()
        ])
            ->assertForbidden();
    }
}
