<?php

namespace Tests\Feature;

use App\Models\Note\Note;
use App\Models\Quote\WorldwideQuote;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Class WorldwideQuoteNoteTest
 * @group worldwide
 */
class WorldwideQuoteNoteTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test can view paginated listing of notes on worldwide quote.
     *
     * @return void
     */
    public function testCanViewPaginatedListingOfNotesOnWorldwideQuote(): void
    {
        $this->authenticateApi();

        $wwQuote = factory(WorldwideQuote::class)->create();

        Note::factory()
//            ->count(30)
            ->hasAttached($wwQuote, relationship: 'worldwideQuotesHaveNote')
            ->for(User::factory(), 'owner')
            ->create();

        $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/notes')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'is_system',
                        'permissions' => [
                            'update',
                            'delete',
                        ],
                        'created_at',
                        'updated_at',
                        'user' => [
                            'id', 'first_name', 'last_name',
                        ],
                    ],
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
                'total',
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
            'user_id' => auth()->id(),
        ]);

        $data = Note::factory()->raw();
        $data['text'] = Arr::pull($data, 'note');

        $response = $this->postJson('api/ww-quotes/'.$wwQuote->getKey().'/notes', $data)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id', 'user_id', 'note', 'created_at',
            ]);

        $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/notes/'.$response->json('id'))->assertOk()
            ->assertJsonStructure([
                'id', 'user_id', 'note', 'created_at',
            ]);
    }

    /**
     * Test can update an existing note on worldwide quote.
     *
     * @return void
     */
    public function testCanUpdateNoteOnWorldwideQuote(): void
    {
        $this->authenticateApi();

        $wwQuote = factory(WorldwideQuote::class)->create();

        $note = Note::factory()
            ->hasAttached($wwQuote, relationship: 'worldwideQuotesHaveNote')
            ->for(User::factory(), 'owner')
            ->create();

        $data = Note::factory()->raw();
        $data['text'] = Arr::pull($data, 'note');

        $response = $this->patchJson('api/ww-quotes/'.$wwQuote->getKey().'/notes/'.$note->getKey(), $data)
            ->assertOk()
            ->assertJsonStructure([
                'id', 'user_id', 'note', 'created_at',
            ]);

        $response = $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/notes/'.$response->json('id'))->assertOk()
            ->assertJsonStructure([
                'id', 'user_id', 'note', 'created_at',
            ]);

        $this->assertEquals($data['text'], $response->json('note'));
    }

    /**
     * Test can delete an existing note on worldwide quote.
     *
     * @return void
     */
    public function testCanDeleteNoteOnWorldwideQuote(): void
    {
        $this->authenticateApi();

        $wwQuote = factory(WorldwideQuote::class)->create();

        $note = Note::factory()
            ->hasAttached($wwQuote, relationship: 'worldwideQuotesHaveNote')
            ->for(User::factory(), 'owner')
            ->create();

        $this->deleteJson('api/ww-quotes/'.$wwQuote->getKey().'/notes/'.$note->getKey())
            ->assertNoContent();

        $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/notes/'.$note->getKey())
            ->assertNotFound();
    }
}
