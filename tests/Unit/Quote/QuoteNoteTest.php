<?php

namespace Tests\Unit\Quote;

use App\Models\Note\Note;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\TestCase;
use Tests\Unit\Traits\{WithFakeQuote, WithFakeUser,};

/**
 * @group build
 */
class QuoteNoteTest extends TestCase
{
    use WithFakeUser, WithFakeQuote, DatabaseTransactions;

    /**
     * Test an ability to view paginated notes of quote.
     *
     * @return void
     */
    public function testCanViewPaginatedNotesOfQuote(): void
    {
        $quote = $this->createQuote($this->user);

        Note::factory()
            ->hasAttached($quote, relationship: 'rescueQuotesHaveNote')
            ->create();

        $response = $this->getJson('api/quotes/notes/'.$quote->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'user_id', 'text', 'created_at', 'updated_at',
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
     *
     * @return void
     */
    public function testCanCreateNoteForQuote(): void
    {
        $quote = $this->createQuote($this->user);

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
     *
     * @return void
     */
    public function testCanUpdateNoteOfQuote(): void
    {
        $quote = $this->createQuote($this->user);

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
     *
     * @return void
     */
    public function testCanDeleteNoteOfQuote(): void
    {
        $quote = $this->createQuote($this->user);

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
