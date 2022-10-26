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

        $r = $this->getJson('api/pipeliner/sync-errors')
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
                        'archived_at',
                        'resolved_at',
                    ],
                ],
            ]);

        $this->assertNotEmpty($r->json('data'));

        foreach ($r->json('data') as $item) {
            $this->assertEmpty($item['archived_at']);
        }
    }

    /**
     * Test an ability to filter archived paginated sync errors.
     */
    public function testCanFilterArchivedPaginatedSyncErrors(): void
    {
        $this->authenticateApi();

        PipelinerSyncError::factory(2)->archived()->create();

        $r = $this->getJson('api/pipeliner/sync-errors?only_archived=true')
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
                        'archived_at',
                        'resolved_at',
                    ],
                ],
            ]);

        $this->assertNotEmpty($r->json('data'));

        foreach ($r->json('data') as $item) {
            $this->assertNotEmpty($item['archived_at']);
        }
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
                'resolved_at',
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
                'resolved_at',
            ]);

        $this->assertNotEmpty($r->json('archived_at'), 'archived_at');
    }

    /**
     * Test an ability to batch archive existing sync errors.
     */
    public function testCanBatchArchiveSyncError(): void
    {
        $this->authenticateApi();

        $errors = PipelinerSyncError::factory()->count(2)->create();

        $this->patchJson("api/pipeliner/sync-errors/batch-archive", [
            'sync_errors' => $errors->map->only('id')->all(),
        ])
//            ->dump()
            ->assertNoContent();

        foreach ($errors as $error) {
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
                    'resolved_at',
                ]);

            $this->assertNotEmpty($r->json('archived_at'), 'archived_at');
        }
    }

    /**
     * Test an ability to archive all existing sync errors.
     */
    public function testCanArchiveAllSyncErrors(): void
    {
        $this->authenticateApi();

        $errors = PipelinerSyncError::factory()->count(2)->create();

        $this->patchJson("api/pipeliner/sync-errors/all-archive")
//            ->dump()
            ->assertNoContent();

        foreach ($errors as $error) {
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
                    'resolved_at',
                ]);

            $this->assertNotEmpty($r->json('archived_at'), 'archived_at');
        }
    }

    /**
     * Test an ability to restore an existing sync error from archive.
     */
    public function testCanRestoreSyncErrorFromArchive(): void
    {
        $this->authenticateApi();

        $error = PipelinerSyncError::factory()->archived()->create();

        $this->patchJson("api/pipeliner/sync-errors/{$error->getKey()}/restore")
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
                'resolved_at',
            ]);

        $this->assertEmpty($r->json('archived_at'), 'archived_at');
    }

    /**
     * Test an ability to batch restore existing sync errors from archive.
     */
    public function testCanBatchRestoreSyncErrorFromArchive(): void
    {
        $this->authenticateApi();

        $errors = PipelinerSyncError::factory()->count(2)->archived()->create();

        $this->patchJson("api/pipeliner/sync-errors/batch-restore", [
            'sync_errors' => $errors->map->only('id')->all(),
        ])
//            ->dump()
            ->assertNoContent();

        foreach ($errors as $error) {
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
                    'resolved_at',
                ]);

            $this->assertEmpty($r->json('archived_at'), 'archived_at');
        }
    }

    /**
     * Test an ability to restore all existing sync errors from archive.
     */
    public function testCanRestoreAllSyncErrorFromArchive(): void
    {
        $this->authenticateApi();

        $errors = PipelinerSyncError::factory()->count(2)->archived()->create();

        $this->patchJson("api/pipeliner/sync-errors/all-restore")
//            ->dump()
            ->assertNoContent();

        foreach ($errors as $error) {
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
                    'resolved_at',
                ]);

            $this->assertEmpty($r->json('archived_at'), 'archived_at');
        }
    }
}
