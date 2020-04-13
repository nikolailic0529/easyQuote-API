<?php

namespace Tests\Unit\Quote;

use App\Events\QuoteNoteCreated;
use App\Listeners\QuoteNoteCreatedListener;
use Tests\TestCase;
use App\Models\Quote\QuoteNote;
use Illuminate\Support\Facades\Event;
use Tests\Unit\Traits\{
    AssertsListing,
    WithFakeQuote,
    WithFakeUser,
};

class QuoteNoteTest extends TestCase
{
    use AssertsListing, WithFakeUser, WithFakeQuote;

    /**
     * Test quote notes listing.
     *
     * @return void
     */
    public function testQuoteNoteListing()
    {
        $response = $this->getJson(url('api/quotes/notes/'.$this->quote->id));

        $this->assertListing($response);
    }

    /**
     * Test creating a new note for specific quote.
     *
     * @return void
     */
    public function testQuoteNoteCreating()
    {
        Event::fake([
            QuoteNoteCreated::class
        ]);

        Event::hasListeners(QuoteNoteCreatedListener::class);

        $attributes = factory(QuoteNote::class)->raw();

        $response = $this->postJson(url('api/quotes/notes/'.$this->quote->id), $attributes)->assertCreated()
            ->assertJsonStructure(['id', 'text', 'quote_id', 'user_id']);

        $id = $response->json('id');

        Event::assertDispatched(QuoteNoteCreated::class, fn (QuoteNoteCreated $event) => $id === $event->quoteNote->id);
    }

    /**
     * Test updating a newly created quote note.
     *
     * @return void
     */
    public function testQuoteNoteUpdating()
    {
        $quoteNote = factory(QuoteNote::class)->create([
            'quote_id' => $this->quote->id
        ]);

        $attributes = factory(QuoteNote::class)->raw();

        $this->patchJson(url('api/quotes/notes/'.$this->quote->id.'/'.$quoteNote->id), $attributes)->assertOk()
            ->assertJsonStructure(['id', 'text', 'quote_id', 'user_id'])
            ->assertJsonFragment(['text' => $attributes['text']]);
    }

    /**
     * Test deleting a newly create quote note.
     *
     * @return void
     */
    public function testQuoteNoteDeleting()
    {
        $quoteNote = factory(QuoteNote::class)->create([
            'quote_id' => $this->quote->id
        ]);

        $this->deleteJson(url('api/quotes/notes/'.$this->quote->id.'/'.$quoteNote->id))->assertOk()
            ->assertExactJson([true]);

        $this->assertSoftDeleted($quoteNote);
    }
}
