<?php

namespace Tests\Unit\Quote;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\Traits\WithFakeQuote;
use Tests\Unit\Traits\WithFakeUser;

class QuoteTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser, WithFakeQuote;

    /**
     * Test updating an existing submitted quote.
     *
     * @return void
     */
    public function testUpdatingSubmittedQuote()
    {
        $this->quote->submit();

        $state = [
            'quote_id' => $this->quote->id
        ];

        $response = $this->postJson(url('api/quotes/state'), $state, $this->authorizationHeader);

        $response->assertForbidden()
            ->assertJsonFragment([
                'message' => QSU_01
            ]);
    }

    /**
     * Test updating an existing drafted quote.
     *
     * @return void
     */
    public function testUpdatingDraftedQuote()
    {
        $this->quote->unSubmit();

        $state = [
            'quote_id' => $this->quote->id
        ];

        $response = $this->postJson(url('api/quotes/state'), $state, $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson(['id' => $this->quote->id]);
    }

    /**
     * Test moving quote to drafted.
     *
     * @return void
     */
    public function testUnsubmitQuote()
    {
        $this->quote->submit();

        $response = $this->putJson(url("api/quotes/submitted/unsubmit/{$this->quote->id}"), [], $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson([true]);

        $this->quote->refresh();

        $this->assertNull($this->quote->submitted_at);
    }
}
