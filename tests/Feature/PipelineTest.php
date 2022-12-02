<?php

namespace Tests\Feature;

use App\Models\Pipeline\Pipeline;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group build
 */
class PipelineTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to view paginated pipeline entities.
     */
    public function testCanViewPaginatedPipelines(): void
    {
        $this->authenticateApi();

        $this->getJson('api/pipelines')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'space_name',
                        'pipeline_name',
                        'is_system',
                        'is_default',

                        'permissions' => [
                            'view',
                            'update',
                            'delete'
                        ],

                        'created_at',
                        'updated_at'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total'
                ]
            ]);

        $this->getJson('api/pipelines?order_by_created_at=desc')->assertOk();
        $this->getJson('api/pipelines?order_by_updated_at=desc')->assertOk();
        $this->getJson('api/pipelines?order_by_pipeline_name=desc')->assertOk();
        $this->getJson('api/pipelines?order_by_space_name=desc')->assertOk();
        $this->getJson('api/pipelines?order_by_is_system=desc')->assertOk();
        $this->getJson('api/pipelines?order_by_is_default=desc')->assertOk();
    }

    /**
     * Test an ability to view list of pipeline entities.
     */
    public function testCanViewListOfPipelines(): void
    {
        $this->authenticateApi();

        $this->getJson('api/pipelines/list')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'space_id',
                    'pipeline_name',
                    'is_default',
                    'opportunity_form_exists',
                ]
            ]);

        // Test with includes.
        $this->getJson('api/pipelines/list?include[]=pipeline_stages')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'space_id',
                    'pipeline_name',
                    'is_default',
                    'pipeline_stages' => [
                        '*' => [
                            'id',
                            'pipeline_id',
                            'stage_name',
                            'stage_order',
                        ]
                    ]
                ]
            ]);

        // Test with filtering by space.
        $this->getJson('api/pipelines/list?'.Arr::query([
                'filter' => [
                    'space_id' => SP_EPD,
                ]
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'space_id',
                    'pipeline_name',
                    'is_default',
                ]
            ]);
    }

    /**
     * Test an ability to view list of pipeline entities with opportunity form.
     */
    public function testCanViewListOfPipelinesWithoutOpportunityForm(): void
    {
        factory(Pipeline::class, 10)->create();

        $this->authenticateApi();

        $this->getJson('api/pipelines/list/without-opportunity-form')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'pipeline_name'
                ]
            ]);
    }

    /**
     * Test an ability to create a new pipeline entity.
     */
    public function testCanCreateNewPipeline(): void
    {
        $this->authenticateApi();

        $this->postJson('api/pipelines', [
            'space_id' => SP_EPD,
            'pipeline_name' => Str::random(40),
            'pipeline_stages' => [
                [
                    'id' => null,
                    'stage_name' => Str::random(40),
                    'stage_percentage' => 10,
                ]
            ]
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'space_id',
                'pipeline_name',
                'pipeline_stages' => [
                    '*' => [
                        'id',
                        'pipeline_id',
                        'stage_name',
                        'stage_order'
                    ]
                ],
                'created_at',
                'updated_at'
            ]);
    }

    /**
     * Test an ability to create an existing pipeline entity.
     */
    public function testCanUpdateExistingPipeline(): void
    {
        $this->authenticateApi();

        $response = $this->postJson('api/pipelines', [
            'space_id' => SP_EPD,
            'pipeline_name' => Str::random(40),
            'pipeline_stages' => [
                [
                    'id' => null,
                    'stage_name' => Str::random(40),
                    'stage_percentage' => 12.5
                ]
            ]
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'space_id',
                'pipeline_name',
                'pipeline_stages' => [
                    '*' => [
                        'id',
                        'pipeline_id',
                        'stage_name',
                        'stage_order'
                    ]
                ],
                'created_at',
                'updated_at'
            ]);

        $updatePipelineData = [
            'space_id' => $response->json('space_id'),
            'pipeline_name' => Str::random(40),
            'pipeline_stages' => [
                [
                    'id' => null,
                    'stage_name' => Str::random(40),
                    'stage_percentage' => 1,
                ],
                [
                    'id' => $existingStageKey = $response->json('pipeline_stages.0.id'),
                    'stage_name' => Str::random(40),
                    'stage_percentage' => 100,
                ],
            ]

        ];

        $response = $this->patchJson('api/pipelines/'.$response->json('id'), $updatePipelineData)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'space_id',
                'pipeline_name',
                'pipeline_stages' => [
                    '*' => [
                        'id',
                        'pipeline_id',
                        'stage_name',
                        'stage_order'
                    ]
                ],
                'created_at',
                'updated_at'
            ]);

        $this->assertNotEmpty($response->json('pipeline_stages'));
        $this->assertCount(2, $response->json('pipeline_stages'));

        $pipelineStagesDictionary = value(function () use ($response): array {
            $dictionary = [];

            foreach ($response->json('pipeline_stages') as $stage) {
                $dictionary[$stage['id']] = $stage;
            }

            return $dictionary;
        });

        $this->assertArrayHasKey($existingStageKey, $pipelineStagesDictionary);
        $this->assertSame(2, $pipelineStagesDictionary[$existingStageKey]['stage_order']);
    }

    /**
     * Test an ability to batch put pipeline entities.
     */
    public function testCanBatchPutPipelines(): void
    {
        $this->authenticateApi();

        /** @var Pipeline $existingPipeline */
        $existingPipeline = factory(Pipeline::class)->create();

        $pipelinesData = [
            [
                'id' => $existingPipeline->getKey(),
                'space_id' => SP_EPD,
                'pipeline_name' => $existingPipeline->pipeline_name,
                'pipeline_stages' => [
                    [
                        'id' => null,
                        'stage_name' => Str::random(40),
                        'stage_percentage' => 1,
                    ]
                ],
                'is_default' => false,
            ],
            [
                'id' => null,
                'space_id' => SP_EPD,
                'pipeline_name' => Str::random(40),
                'pipeline_stages' => [
                    [
                        'id' => null,
                        'stage_name' => Str::random(40),
                        'stage_percentage' => 1,
                    ]
                ],
                'is_default' => true,
            ]
        ];

        $this->putJson('api/pipelines', [
            'pipelines' => $pipelinesData
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'space_id',
                    'is_system',
                    'pipeline_name',
                    'pipeline_stages' => [
                        '*' => [
                            'id',
                            'pipeline_id',
                            'stage_name',
                            'stage_order',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'created_at',
                    'updated_at'
                ]
            ]);

    }

    /**
     * Test an ability to mark pipeline entity as default.
     */
    public function testCanMarkPipelineAsDefault(): void
    {
        $pipelines = factory(Pipeline::class, 2)->create();

        $this->authenticateApi();

        $this->patchJson('api/pipelines/'.$pipelines[0]->getKey().'/default')
            ->assertNoContent();

        $this->getJson('api/pipelines/'.$pipelines[0]->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'is_default'
            ])
            ->assertJsonFragment([
                'is_default' => true
            ]);

        $this->getJson('api/pipelines/'.$pipelines[1]->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'is_default'
            ])
            ->assertJsonFragment([
                'is_default' => false
            ]);

        $this->patchJson('api/pipelines/'.$pipelines[1]->getKey().'/default')
            ->assertNoContent();

        $this->getJson('api/pipelines/'.$pipelines[1]->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'is_default'
            ])
            ->assertJsonFragment([
                'is_default' => true
            ]);

        $this->getJson('api/pipelines/'.$pipelines[0]->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'is_default'
            ])
            ->assertJsonFragment([
                'is_default' => false
            ]);
    }

    /**
     * Test an ability to delete an existing pipeline entity.
     */
    public function testCanDeleteExistingPipeline(): void
    {
        $pipeline = factory(Pipeline::class)->create();

        $this->authenticateApi();

        $this->getJson('api/pipelines/'.$pipeline->getKey())
//            ->dump()
            ->assertOk();

        $this->deleteJson('api/pipelines/'.$pipeline->getKey())
            ->assertOk();

        $this->getJson('api/pipelines/'.$pipeline->getKey())
//            ->dump()
            ->assertNotFound();
    }

    /**
     * Test an ability to view opportunity form schema of an existing pipeline entity.
     */
    public function testCanViewOpportunityFormSchemaOfExistingPipeline(): void
    {
        $this->authenticateApi();

        $response = $this->getJson('api/pipelines/list')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'pipeline_name'
                ]
            ]);

        $this->assertNotEmpty($pipelineKey = $response->json('0.id'));

        $response = $this->getJson('api/pipelines/'.$pipelineKey.'/opportunity-form')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'form_data'
            ]);

        $this->assertIsArray($response->json('form_data'));
    }

    /**
     * Test an ability to view opportunity form schema of the default pipeline entity.
     */
    public function testCanViewOpportunityFormSchemaOfDefaultPipeline(): void
    {
        $this->authenticateApi();

        $response = $this->getJson('api/pipelines/default/opportunity-form')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'form_data'
            ]);

        $this->assertIsArray($response->json('form_data'));
    }

    /**
     * Test an ability to view the default pipeline entity.
     */
    public function testCanViewDefaultPipeline(): void
    {
        $this->authenticateApi();

        $this->getJson('api/pipelines/default')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'space_id',
                'pipeline_name',
                'pipeline_stages' => [
                    '*' => [
                        'id',
                        'pipeline_id',
                        'stage_name',
                        'stage_order'
                    ]
                ],
                'created_at',
                'updated_at'
            ]);
    }

    /**
     * Test an ability to view the specified pipeline entity.
     */
    public function testCanViewSpecifiedPipeline(): void
    {
        $pipeline = factory(Pipeline::class)->create();

        $this->authenticateApi();

        $this->getJson('api/pipelines/'.$pipeline->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'space_id',
                'pipeline_name',
                'pipeline_stages' => [
                    '*' => [
                        'id',
                        'pipeline_id',
                        'stage_name',
                        'stage_order'
                    ]
                ],
                'created_at',
                'updated_at'
            ]);
    }
}
