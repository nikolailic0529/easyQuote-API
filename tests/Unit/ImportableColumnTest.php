<?php

namespace Tests\Unit;

use App\Models\QuoteFile\ImportableColumn;
use App\Models\QuoteFile\ImportableColumnAlias;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\Traits\AssertsListing;
use Tests\Unit\Traits\WithFakeUser;

/**
 * @group build
 */
class ImportableColumnTest extends TestCase
{
    use WithFakeUser, AssertsListing, DatabaseTransactions;

    protected static array $assertableAttributes = ['id', 'header', 'name', 'type', 'is_system', 'country_id', 'country', 'aliases', 'created_at', 'activated_at'];

    /**
     * Test importable columns listing.
     *
     * @return void
     */
    public function testImportableColumnsListing()
    {
        $query = http_build_query([
            'order_by_created_at' => 'asc',
            'order_by_country_name' => 'asc',
            'order_by_header' => 'asc',
            'order_by_type' => 'asc',
        ]);

        $response = $this->getJson('api/importable-columns?' . $query)->assertOk();

        $this->assertListing($response);
    }

    /**
     * Test creating a new importable column.
     *
     * @return void
     */
    public function testImportableColumnCreating()
    {
        ImportableColumn::whereIsSystem(false)->forceDelete();

        $attributes = factory(ImportableColumn::class)->state('aliases')->raw();

        $this->postJson('api/importable-columns', $attributes)
            ->assertCreated()
            ->assertJsonStructure(static::$assertableAttributes);
    }

    /**
     * Test updating a newly created importable column.
     *
     * @return void
     */
    public function testImportableColumnUpdating()
    {
        ImportableColumn::whereIsSystem(false)->forceDelete();

        $importableColumn = factory(ImportableColumn::class)->create();

        $attributes = factory(ImportableColumn::class)->state('aliases')->raw();

        $this->patchJson('/api/importable-columns/' . $importableColumn->getKey(), $attributes)->assertOk();

        $importableColumn->refresh();

        $this->assertEquals($attributes['header'], $importableColumn['header']);
        $this->assertEquals($attributes['country_id'], $importableColumn['country_id']);
        $this->assertEquals($attributes['type'], $importableColumn['type']);

        $importableColumn->aliases->each(
            fn (ImportableColumnAlias $alias) => $this->assertContains($alias->alias, $attributes['aliases'])
        );
    }

    /**
     * Test deleting a newly created importable column.
     *
     * @return void
     */
    public function testImportableColumnDeleting()
    {
        ImportableColumn::whereIsSystem(false)->forceDelete();

        $importableColumn = factory(ImportableColumn::class)->create();

        $this->deleteJson('/api/importable-columns/'.$importableColumn->getKey())->assertOk();

        $this->assertSoftDeleted($importableColumn);
    }
}
