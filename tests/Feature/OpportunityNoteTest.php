<?php

namespace Tests\Feature;

use App\Domain\Note\Models\Note;
use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * @group opportunity
 * @group build
 */
class OpportunityNoteTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->faker);
    }

    /**
     * Test an ability to view paginated notes of opportunity.
     */
    public function testCanViewPaginatedNotesOfOpportunity(): void
    {
        $this->authenticateApi();

        $opportunity = Opportunity::factory()->create();

        Note::factory()
            ->count(2)
            ->hasAttached($opportunity, relationship: 'opportunitiesHaveNote')
            ->for($this->app['auth']->user(), 'owner')
            ->create();

        $response = $this->getJson('api/opportunities/'.$opportunity->getKey().'/notes')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'text',
                        'created_at',
                        'is_system',
                        'permissions' => [
                            'update',
                            'delete',
                        ],
                        'owner' => [
                            'id',
                            'user_fullname',
                        ],
                    ],
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'per_page',
                    'to',
                    'total',
                    'links' => [
                        '*' => [
                            'url', 'label', 'active',
                        ],
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));
    }

    /**
     * Test an ability to create a new opportunity note.
     */
    public function testCanCreateOpportunityNote(): void
    {
        $this->authenticateApi();

        $opportunity = Opportunity::factory()->create();

        $data = [
            'text' => $this->faker->text(),
        ];

        $response = $this->postJson('api/opportunities/'.$opportunity->getKey().'/notes', $data)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'user_id',
                'text',
                'created_at',
            ]);

        $response = $this->getJson('api/opportunity-notes/'.$response->json('id'))
            ->assertOk();

        $this->assertSame($this->app['auth']->id(), $response->json('user_id'));
        $this->assertSame($data['text'], $response->json('text'));
    }

    /**
     * Test an ability to update an existing opportunity note.
     */
    public function testCanUpdateOpportunityNote(): void
    {
        $note = Note::factory()->create();

        $this->authenticateApi();

        $data = [
            'text' => $this->faker->text(),
        ];

        $this->patchJson('api/opportunity-notes/'.$note->getKey(), $data)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'text',
                'created_at',
            ]);

        $response = $this->getJson('api/opportunity-notes/'.$note->getKey())
            ->assertOk();

        $this->assertNotSame($this->app['auth']->id(), $response->json('user_id'));
        $this->assertSame($data['text'], $response->json('text'));
    }

    /**
     * Test an ability to delete an existing opportunity note.
     */
    public function testCanDeleteOpportunityNote(): void
    {
        $note = Note::factory()->create();

        $this->authenticateApi();

        $this->deleteJson('api/opportunity-notes/'.$note->getKey())
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/opportunity-notes/'.$note->getKey())
            ->assertNotFound();
    }
}
