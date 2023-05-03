<?php

namespace Tests\Feature;

use App\Domain\Company\Models\Company;
use App\Domain\Note\Models\Note;
use App\Domain\Rescue\Models\Quote;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group build
 */
class NoteTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to view paginated notes of a company.
     */
    public function testCanViewPaginatedNotesOfCompany(): void
    {
        $this->authenticateApi();

        $note = Note::factory()
            ->hasAttached($related = Company::factory()->create(), relationship: 'companiesHaveNote')
            ->for(User::factory(), 'owner')
            ->create();

        $this->getJson('api/notes/rel-company/'.$related->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'owner' => [
                            'id',
                            'first_name',
                            'last_name',
                            'user_fullname',
                        ],
                        'text',
                        'is_system',
                        'permissions' => [
                            'update',
                            'delete',
                        ],
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
            ])
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test an ability to view paginated notes of an opportunity.
     */
    public function testCanViewPaginatedNotesOfOpportunity(): void
    {
        $this->authenticateApi();

        $note = Note::factory()
            ->hasAttached($related = Opportunity::factory()->create(), relationship: 'opportunitiesHaveNote')
            ->for(User::factory(), 'owner')
            ->create();

        $this->getJson('api/notes/rel-opportunity/'.$related->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'owner' => [
                            'id',
                            'first_name',
                            'last_name',
                            'user_fullname',
                        ],
                        'text',
                        'is_system',
                        'permissions' => [
                            'update',
                            'delete',
                        ],
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
            ])
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test an ability to view paginated notes of a worldwide quote.
     */
    public function testCanViewPaginatedNotesOfWorldwideQuote(): void
    {
        $this->authenticateApi();

        $note = Note::factory()
            ->hasAttached($related = WorldwideQuote::factory()->create(), relationship: 'worldwideQuotesHaveNote')
            ->for(User::factory(), 'owner')
            ->create();

        $this->getJson('api/notes/rel-ww-quote/'.$related->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'owner' => [
                            'id',
                            'first_name',
                            'last_name',
                            'user_fullname',
                        ],
                        'text',
                        'is_system',
                        'permissions' => [
                            'update',
                            'delete',
                        ],
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
            ])
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test an ability to view paginated notes of a rescue quote.
     */
    public function testCanViewPaginatedNotesOfRescueQuote(): void
    {
        $this->authenticateApi();

        $note = Note::factory()
            ->hasAttached($related = Quote::factory()->create(), relationship: 'rescueQuotesHaveNote')
            ->for(User::factory(), 'owner')
            ->create();

        $this->getJson('api/notes/rel-quote/'.$related->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'owner' => [
                            'id',
                            'first_name',
                            'last_name',
                            'user_fullname',
                        ],
                        'text',
                        'is_system',
                        'permissions' => [
                            'update',
                            'delete',
                        ],
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
            ])
            ->assertJsonCount(1, 'data');
    }

    public function testCanCreateNoteForCompany(): void
    {
        $this->authenticateApi();

        $related = Company::factory()->create();

        $r = $this->postJson('api/notes/rel-company/'.$related->getKey(), $data = [
            'text' => Str::random(20_000),
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                ],
            ]);

        $this->getJson('api/notes/'.$r->json('data.id'))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'text',
                ],
            ])
            ->assertJsonPath('data.text', $data['text']);
    }

    public function testCanCreateNoteForRescueQuote(): void
    {
        $this->authenticateApi();

        $related = Quote::factory()->create();

        $r = $this->postJson('api/notes/rel-quote/'.$related->getKey(), $data = [
            'text' => Str::random(20_000),
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                ],
            ]);

        $this->getJson('api/notes/'.$r->json('data.id'))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'text',
                ],
            ])
            ->assertJsonPath('data.text', $data['text']);
    }

    public function testCanCreateNoteForWorldwideQuote(): void
    {
        $this->authenticateApi();

        $related = WorldwideQuote::factory()->create();

        $r = $this->postJson('api/notes/rel-ww-quote/'.$related->getKey(), $data = [
            'text' => Str::random(20_000),
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                ],
            ]);

        $this->getJson('api/notes/'.$r->json('data.id'))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'text',
                ],
            ])
            ->assertJsonPath('data.text', $data['text']);
    }

    public function testCanCreateNoteForOpportunity(): void
    {
        $this->authenticateApi();

        $related = Opportunity::factory()->create();

        $r = $this->postJson('api/notes/rel-opportunity/'.$related->getKey(), $data = [
            'text' => Str::random(20_000),
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                ],
            ]);

        $this->getJson('api/notes/'.$r->json('data.id'))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'text',
                ],
            ])
            ->assertJsonPath('data.text', $data['text']);
    }

    /**
     * Test an ability to update an existing note.
     */
    public function testCanUpdateNote(): void
    {
        $this->authenticateApi();

        $note = Note::factory()->create();

        $this->patchJson('api/notes/'.$note->getKey(), [
            'text' => $newNoteText = Str::random(2000),
        ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'text',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath(
                'data.text', $newNoteText,
            );

        $this->getJson('api/notes/'.$note->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'text',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath(
                'data.text', $newNoteText,
            );
    }

    /**
     * Test an ability to delete an existing note.
     */
    public function testCanDeleteNote(): void
    {
        $this->authenticateApi();

        $note = Note::factory()->create();

        $this->getJson('api/notes/'.$note->getKey())
            ->assertOk();

        $this->deleteJson('api/notes/'.$note->getKey())
            ->assertNoContent();

        $this->getJson('api/notes/'.$note->getKey())
            ->assertNotFound();
    }
}
