<?php

namespace Tests\Feature;

use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteNote;
use App\Notifications\WorldwideQuote\NoteCreated;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Class WorldwideQuoteNoteTest
 * @group worldwide
 */
class WorldwideQuoteNoteTest extends TestCase
{

    /**
     * Test can view paginated listing of notes on worldwide quote.
     *
     * @return void
     */
    public function testCanViewPaginatedListingOfNotesOnWorldwideQuote()
    {
        $this->authenticateApi();

        $wwQuote = factory(WorldwideQuote::class)->create();

        $wwNotes = factory(WorldwideQuoteNote::class, 30)->create([
            'worldwide_quote_id' => $wwQuote->getKey(),
            'user_id' => auth()->id()
        ]);

        $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/notes')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'worldwide_quote_id',
                        'user_id',
                        'created_at',
                        'updated_at',
                        'user' => [
                            'id', 'first_name', 'last_name'
                        ]
                    ]
                ],
                'current_page',
                'from',
                'last_page',
                'path',
                'per_page',
//                'first_page_url',
//                'prev_page_url',
//                'last_page_url',
//                'next_page_url',
                'to',
                'total'
            ]);
    }

    /**
     * Test can create note on worldwide quote.
     *
     * @return void
     */
    public function testCanCreateNoteOnWorldwideQuote()
    {
        Notification::fake();

        $this->authenticateApi();

        $wwQuote = factory(WorldwideQuote::class)->create([
            'user_id' => auth()->id()
        ]);

        $data = factory(WorldwideQuoteNote::class)->raw();

        $response = $this->postJson('api/ww-quotes/'.$wwQuote->getKey().'/notes', $data)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id', 'worldwide_quote_id', 'user_id', 'text', 'created_at'
            ]);

        Notification::assertSentTo(auth()->user(), NoteCreated::class);

        $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/notes/'.$response->json('id'))->assertOk()
            ->assertJsonStructure([
                'id', 'worldwide_quote_id', 'user_id', 'text', 'created_at'
            ]);
    }

    /**
     * Test can update an existing note on worldwide quote.
     *
     * @return void
     */
    public function testCanUpdateNoteOnWorldwideQuote()
    {
        $this->authenticateApi();

        $wwQuote = factory(WorldwideQuote::class)->create();

        $wwNote = factory(WorldwideQuoteNote::class)->create([
            'worldwide_quote_id' => $wwQuote->getKey(),
            'user_id' => auth()->id()
        ]);

        $data = factory(WorldwideQuoteNote::class)->raw();

        $response = $this->patchJson('api/ww-quotes/'.$wwQuote->getKey().'/notes/'.$wwNote->getKey(), $data)
            ->assertOk()
            ->assertJsonStructure([
                'id', 'worldwide_quote_id', 'user_id', 'text', 'created_at'
            ]);

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/notes/'.$response->json('id'))->assertOk()
            ->assertJsonStructure([
                'id', 'worldwide_quote_id', 'user_id', 'text', 'created_at'
            ]);

        $this->assertEquals($data['text'], $response->json('text'));
    }

    /**
     * Test can delete an existing note on worldwide quote.
     *
     * @return void
     */
    public function testCanDeleteNoteOnWorldwideQuote()
    {
        $this->authenticateApi();

        $wwQuote = factory(WorldwideQuote::class)->create();

        $wwNote = factory(WorldwideQuoteNote::class)->create([
            'worldwide_quote_id' => $wwQuote->getKey(),
            'user_id' => auth()->id()
        ]);

        $this->deleteJson('api/ww-quotes/'.$wwQuote->getKey().'/notes/'.$wwNote->getKey())
            ->assertNoContent();

        $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/notes/'.$wwNote->getKey())
            ->assertNotFound();
    }
}
