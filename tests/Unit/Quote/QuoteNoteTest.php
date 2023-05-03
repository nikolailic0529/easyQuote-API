<?php

namespace Tests\Unit\Quote;

use App\Domain\Note\Models\Note;
use App\Domain\Rescue\Models\Quote;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\TestCase;

/**
 * @group build
 */
class QuoteNoteTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to view paginated notes of quote.
     */
    public function testCanViewPaginatedNotesOfQuote(): void
    {
        $this->authenticateApi();

        $quote = Quote::factory()->create();

        Note::factory()
            ->hasAttached($quote, relationship: 'rescueQuotesHaveNote')
            ->create();

        $response = $this->getJson('api/quotes/notes/'.$quote->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
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
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
                'links' => [
                    '*' => ['url', 'label', 'active'],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));
    }

    /**
     * Test an ability to create a new note for a quote.
     */
    public function testCanCreateNoteForQuote(): void
    {
        $this->authenticateApi();

        $quote = Quote::factory()->create();

        $data = Note::factory()->raw();
        $data['text'] = Arr::pull($data, 'note');

        $this->postJson('api/quotes/notes/'.$quote->getKey(), $data)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'text',
                'user_id',
            ]);
    }

    /**
     * Test updating a newly created quote note.
     */
    public function testCanUpdateNoteOfQuote(): void
    {
        $this->authenticateApi();

        $quote = Quote::factory()->create();

        $note = Note::factory()
            ->hasAttached($quote, relationship: 'rescueQuotesHaveNote')
            ->create();

        $data = Note::factory()->raw();
        $data['text'] = Arr::pull($data, 'note');

        $this->patchJson('api/quotes/notes/'.$quote->getKey().'/'.$note->getKey(), $data)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'text',
                'user_id',
            ])
            ->assertJsonFragment(['text' => $data['text']]);
    }

    /**
     * Test deleting a newly create quote note.
     */
    public function testCanDeleteNoteOfQuote(): void
    {
        $this->authenticateApi();

        $quote = Quote::factory()->create();

        $note = Note::factory()
            ->hasAttached($quote, relationship: 'rescueQuotesHaveNote')
            ->create();

        $this->deleteJson('api/quotes/notes/'.$quote->getKey().'/'.$note->getKey())
            ->assertNoContent();

        $this->getJson('api/quotes/notes/'.$quote->getKey().'/'.$note->getKey())
//            ->dump()
            ->assertNotFound();
    }
}
