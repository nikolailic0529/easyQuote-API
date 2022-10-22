<?php

namespace Tests\Feature;

use App\Models\Pipeliner\PipelinerSyncError;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PipelinerSyncErrorTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to view paginated sync errors.
     */
    public function testCanViewPaginatedSyncErrors(): void
    {
        $this->authenticateApi();

        PipelinerSyncError::factory(2)->create();

        $this->getJson('api/pipeliner/sync-errors')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'entity_id',
                        'entity_type',
                        'entity_name',
                        'error_message',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    /**
     * Test an ability to view an existing sync error.
     */
    public function testCanViewSyncError(): void
    {
        $this->authenticateApi();

        $error = PipelinerSyncError::factory()->create();

        $this->getJson("api/pipeliner/sync-errors/{$error->getKey()}")
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'entity_id',
                'entity_type',
                'entity_name',
                'error_message',
                'created_at',
                'updated_at',
                'archived_at',
            ]);
    }

    /**
     * Test an ability to archive an existing sync error.
     */
    public function testCanArchiveSyncError(): void
    {
        $this->authenticateApi();

        $error = PipelinerSyncError::factory()->create();

        $this->patchJson("api/pipeliner/sync-errors/{$error->getKey()}/archive")
//            ->dump()
           ->assertNoContent();

        $r = $this->getJson("api/pipeliner/sync-errors/{$error->getKey()}")
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'entity_id',
                'entity_type',
                'entity_name',
                'error_message',
                'created_at',
                'updated_at',
                'archived_at',
            ]);

        $this->assertNotEmpty($r->json('archived_at'), 'archived_at');
    }
}
