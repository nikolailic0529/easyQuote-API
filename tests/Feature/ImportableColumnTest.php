<?php

namespace Tests\Feature;

use App\Models\Data\Country;
use App\Models\QuoteFile\ImportableColumn;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Unit\Traits\WithFakeUser;

/**
 * @group build
 */
class ImportableColumnTest extends TestCase
{
    use WithFakeUser, DatabaseTransactions;

    /**
     * Test importable columns listing.
     *
     * @return void
     */
    public function testCanViewListingOfImportableColumns()
    {
        $this->getJson('api/importable-columns')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'header',
                        'name',
                        'type',
                        'is_system',
                        'country_id',
                        'country' => [
                            'name',
                        ],
                        'created_at',
                        'activated_at',
                    ],
                ],
                'current_page',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'links' => [
                    '*' => [
                        'url', 'label', 'active',
                    ],
                ],
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);

        foreach ([
                     'created_at',
                     'country_name',
                     'header',
                     'type',
                 ] as $column) {
            $this->getJson('api/importable-columns?order_by_'.$column.'=asc')->assertOk();
            $this->getJson('api/importable-columns?order_by_'.$column.'=desc')->assertOk();
        }

        $this->getJson('api/importable-columns?'.Arr::query([
                'search' => 'product no',
            ]))
//            ->dump()
            ->assertOk();
    }

    /**
     * Test an ability to create a new importable column.
     *
     * @return void
     */
    public function testCanCreateImportableColumn()
    {
        $this->app['db.connection']->table('importable_columns')->where('is_system', false)->delete();

        $attributes = factory(ImportableColumn::class)->state('aliases')->raw();

        $response = $this->postJson('api/importable-columns', [
            'header' => Str::random(40),
            'country_id' => Country::query()->where('iso_3166_2', 'GB')->value('id'),
            'type' => 'text',
            'aliases' => $aliases = [
                Str::random(40),
                Str::random(40),
                Str::random(40),
                Str::random(40),
                Str::random(40),
            ],
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'header',
                'name',
                'type',
                'is_system',
                'country_id',
                'country',
                'aliases' => [
                    '*' => [
                        'id', 'alias',
                    ],
                ],
                'created_at',
                'activated_at',
            ]);

        $this->assertNotEmpty($response->json('aliases'));
        $this->assertCount(count($aliases), $response->json('aliases'));

        foreach ($aliases as $alias) {
            $this->assertContains($alias, $response->json('aliases.*.alias'));
        }
    }

    /**
     * Test an ability to update a particular importable column.
     *
     * @return void
     */
    public function testCanUpdateImportableColumn()
    {
        $this->app['db.connection']->table('importable_columns')->where('is_system', false)->delete();

        /** @var ImportableColumn $importableColumn */
        $importableColumn = factory(ImportableColumn::class)->create([
            'country_id' => Country::query()->where('iso_3166_2', 'GB')->value('id'),
            'type' => 'decimal',
        ]);

        $this->patchJson('api/importable-columns/'.$importableColumn->getKey().'?include[]=aliases', [
            'header' => $newHeader = Str::random(40),
            'country_id' => $newCountry = Country::query()->where('iso_3166_2', 'US')->value('id'),
            'type' => $newType = 'text',
            'aliases' => $newAliases = [
                Str::random(40),
                Str::random(40),
                Str::random(40),
            ],
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/importable-columns/'.$importableColumn->getKey().'?include[]=aliases')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'header',
                'country_id',
                'type',
                'aliases' => [
                    '*' => [
                        'id', 'alias',
                    ],
                ],
            ]);

        $this->assertSame($newHeader, $response->json('header'));
        $this->assertSame($newCountry, $response->json('country_id'));
        $this->assertSame($newType, $response->json('type'));
        $this->assertCount(count($newAliases), $response->json('aliases'));

        foreach ($newAliases as $alias) {

            $this->assertContains($alias, $response->json('aliases.*.alias'));

        }
    }

    /**
     * Test an ability to delete a particular importable column.
     *
     * @return void
     */
    public function testCanDeleteImportableColumn()
    {
        $this->app['db.connection']->table('importable_columns')->where('is_system', false)->delete();

        $importableColumn = factory(ImportableColumn::class)->create();

        $this->deleteJson('api/importable-columns/'.$importableColumn->getKey())
            ->assertNoContent();

        $this->getJson('api/importable-columns/'.$importableColumn->getKey())
            ->assertNotFound();
    }

    /**
     * Test an ability to mark a particular importable column as active.
     *
     * @return void
     */
    public function testCanMarkImportableColumnAsActive()
    {
        $this->app['db.connection']->table('importable_columns')->where('is_system', false)->delete();

        $importableColumn = factory(ImportableColumn::class)->create([
            'activated_at' => null,
        ]);

        $this->putJson('api/importable-columns/activate/'.$importableColumn->getKey())
            ->assertNoContent();

        $response = $this->getJson('api/importable-columns/'.$importableColumn->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'header',
                'country_id',
                'type',
                'activated_at',
            ]);

        $this->assertNotEmpty($response->json('activated_at'));
    }

    /**
     * Test an ability to mark a particular importable column as inactive.
     *
     * @return void
     */
    public function testCanMarkImportableColumnAsInactive()
    {
        $this->app['db.connection']->table('importable_columns')->where('is_system', false)->delete();

        $importableColumn = factory(ImportableColumn::class)->create([
            'activated_at' => null,
        ]);

        $this->putJson('api/importable-columns/deactivate/'.$importableColumn->getKey())
            ->assertNoContent();

        $response = $this->getJson('api/importable-columns/'.$importableColumn->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'header',
                'country_id',
                'type',
                'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));
    }
}
