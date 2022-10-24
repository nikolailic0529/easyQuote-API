<?php

namespace Tests\Feature;

use App\Events\Pipeliner\AggregateSyncFailed;
use App\Events\Pipeliner\AggregateSyncCompleted;
use App\Events\Pipeliner\AggregateSyncProgress;
use App\Events\Pipeliner\AggregateSyncStarting;
use App\Integrations\Pipeliner\GraphQl\PipelinerDataIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerGraphQlClient;
use App\Integrations\Pipeliner\GraphQl\PipelinerOpportunityIntegration;
use App\Models\Company;
use App\Models\Opportunity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PipelinerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to sync a single model.
     */
    public function testCanSyncSingleModel(): void
    {
        $this->authenticateApi();

        $company = Opportunity::factory()->create();

        $this->patchJson('api/pipeliner/sync-model', [
            'model' => [
                'id' => $company->getKey(),
                'type' => 'Opportunity',
            ],
        ])
//            ->dump()
            ->assertOk();

    }


    /**
     * Test an ability to queue opportunity sync job.
     */
    public function testCanQueueOpportunitySyncJob(): void
    {
        $this->markTestSkipped();

        $this->authenticateApi();

        Event::fake([AggregateSyncCompleted::class, AggregateSyncFailed::class, AggregateSyncProgress::class, AggregateSyncStarting::class]);

        /** @var PipelinerGraphQlClient $oppClient */
        $oppClient = $this->app->make(PipelinerGraphQlClient::class);

//        $oppClient->fakeSequence()
//            ->push(
//                json_decode(file_get_contents(__DIR__.'/Data/pipeliner/opportunities-getByCriteria.json'), true)
//            )
//            ->push(
//                json_decode(file_get_contents(__DIR__.'/Data/pipeliner/opportunities-getByCriteria.json'), true)
//            );

        $oppClient->fake([
            '*' => json_decode(file_get_contents(__DIR__.'/Data/pipeliner/opportunities-getByCriteria.json'), true),
        ]);

        $this->app->when(PipelinerOpportunityIntegration::class)->needs(PipelinerGraphQlClient::class)->give(static function () use ($oppClient) {
            return $oppClient;
        });
//
        /** @var PipelinerGraphQlClient $dataClient */
        $dataClient = $this->app->make(PipelinerGraphQlClient::class);

        $dataClient->fake(static function (Request $request): ?array {
            if (str_contains($request->data()['query'], 'getByIds')) {
                return [
                    'data' => [
                        'entities' => [
                            'data' => [
                                'getByIds' => [],
                            ],
                        ],
                    ],
                ];
            }

            if (str_contains($request->data()['query'], '0d7cfbdc-1dbb-4935-ae48-6de7c5949474')) {
                return [
                    'data' => [
                        'entities' => [
                            'data' => [
                                'getById' => [
                                    'id' => '0d7cfbdc-1dbb-4935-ae48-6de7c5949474',
                                    'optionName' => 'Hardware',
                                    'calcValue' => 1.0,
                                ],
                            ],
                        ],
                    ],
                ];
            }

            return null;
        });

        $this->app->when(PipelinerDataIntegration::class)->needs(PipelinerGraphQlClient::class)->give(static function () use ($dataClient) {
            return $dataClient;
        });

        $this->patchJson('api/opportunities/queue-pipeliner-sync', [
            'strategies' => ['PullTaskStrategy']
        ])
            ->dump()
            ->assertOk()
            ->assertJsonStructure(['queued'])
            ->assertJson(['queued' => true]);

        Event::assertDispatched(AggregateSyncStarting::class, function (AggregateSyncStarting $event): bool {
            $this->assertArrayHasKey('progress', $event->broadcastWith());
            $this->assertArrayHasKey('total_entities', $event->broadcastWith());
            $this->assertArrayHasKey('pending_entities', $event->broadcastWith());

            return true;
        });
        Event::assertDispatched(AggregateSyncCompleted::class);
        Event::assertDispatched(AggregateSyncProgress::class, function (AggregateSyncProgress $event): bool {
            $this->assertArrayHasKey('progress', $event->broadcastWith());
            $this->assertArrayHasKey('total_entities', $event->broadcastWith());
            $this->assertArrayHasKey('pending_entities', $event->broadcastWith());

            return true;
        });
    }

    /**
     * Test an ability to receive notification when queued opportunity sync job is failed.
     */
    public function testCanReceiveNotificationWhenQueuedOpportunitySyncJobFailed(): void
    {
        $this->authenticateApi();

        Event::fake([AggregateSyncCompleted::class, AggregateSyncFailed::class]);

        /** @var PipelinerGraphQlClient $oppClient */
        $oppClient = $this->app->make(PipelinerGraphQlClient::class);

        $oppClient->fake([
            '*' => $oppClient::response(['errors' => [
                'message' => 'A failure happened...',
            ]], 404),
        ]);

        $this->app->when(PipelinerOpportunityIntegration::class)->needs(PipelinerGraphQlClient::class)->give(static function () use ($oppClient) {
            return $oppClient;
        });
//
        /** @var PipelinerGraphQlClient $dataClient */
        $dataClient = $this->app->make(PipelinerGraphQlClient::class);

        $dataClient->fake(static function (Request $request): ?array {
            if (str_contains($request->data()['query'], 'getByIds')) {
                return [
                    'data' => [
                        'entities' => [
                            'data' => [
                                'getByIds' => [],
                            ],
                        ],
                    ],
                ];
            }

            if (str_contains($request->data()['query'], '0d7cfbdc-1dbb-4935-ae48-6de7c5949474')) {
                return [
                    'data' => [
                        'entities' => [
                            'data' => [
                                'getById' => [
                                    'id' => '0d7cfbdc-1dbb-4935-ae48-6de7c5949474',
                                    'optionName' => 'Hardware',
                                    'calcValue' => 1.0,
                                ],
                            ],
                        ],
                    ],
                ];
            }

            return null;
        });

        $this->app->when(PipelinerDataIntegration::class)->needs(PipelinerGraphQlClient::class)->give(static function () use ($dataClient) {
            return $dataClient;
        });

        $this->patchJson('api/opportunities/queue-pipeliner-sync')
//            ->dump()
            ->assertStatus(500);

        Event::assertDispatched(AggregateSyncFailed::class, function (AggregateSyncFailed $event) {
            return str_contains($event->getException()->getMessage(), 'A failure happened');
        });
    }

    /**
     * Test an ability to view pipeliner data sync status.
     */
    public function testCanViewPipelinerSyncStatus(): void
    {
        $this->authenticateApi();

        $this->getJson('api/opportunities/pipeliner-sync-status')
            ->assertJsonStructure(['running'])
            ->assertJson(['running' => false]);
    }
}
