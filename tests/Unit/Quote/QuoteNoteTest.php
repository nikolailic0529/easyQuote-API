<?php

namespace Tests\Unit\Quote;

use App\Events\QuoteNoteCreated;
use App\Listeners\QuoteNoteCreatedListener;
use Tests\TestCase;
use App\Models\Quote\QuoteNote;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\Unit\Traits\{
    AssertsListing,
    WithFakeQuote,
    WithFakeUser,
};

/**
 * @group build
 */
class QuoteNoteTest extends TestCase
{
    use AssertsListing, WithFakeUser, WithFakeQuote, DatabaseTransactions;

    /**
     * Test quote notes listing.
     *
     * @return void
     */
    public function testQuoteNoteListing()
    {
        $quote = $this->createQuote($this->user);
        
        $response = $this->getJson(url('api/quotes/notes/'.$quote->id));

        $this->assertListing($response);
    }

    /**
     * Test creating a new note for specific quote.
     *
     * @return void
     */
    public function testQuoteNoteCreating()
    {
        $quote = $this->createQuote($this->user);

        Event::fake([
            QuoteNoteCreated::class
        ]);

        Event::hasListeners(QuoteNoteCreatedListener::class);

        $attributes = factory(QuoteNote::class)->raw();

        $response = $this->postJson(url('api/quotes/notes/'.$quote->id), $attributes)->assertCreated()
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
        $quote = $this->createQuote($this->user);

        $quoteNote = factory(QuoteNote::class)->create([
            'quote_id' => $quote->id
        ]);

        $attributes = factory(QuoteNote::class)->raw();

        $this->patchJson(url('api/quotes/notes/'.$quote->id.'/'.$quoteNote->id), $attributes)->assertOk()
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
        $quote = $this->createQuote($this->user);

        $quoteNote = factory(QuoteNote::class)->create([
            'quote_id' => $quote->id
        ]);

        $this->deleteJson(url('api/quotes/notes/'.$quote->id.'/'.$quoteNote->id))->assertOk()
            ->assertExactJson([true]);

        $this->assertSoftDeleted($quoteNote);
    }
}
