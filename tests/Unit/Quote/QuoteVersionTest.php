<?php

namespace Tests\Unit\Quote;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\WithFakeQuote;
use Tests\Unit\Traits\WithFakeUser;

class QuoteVersionTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser, WithFakeQuote;

    /**
     * Test a new Version creating when non-author user is editing a Quote.
     *
     * @return void
     */
    public function testVersionCreatingByNonAuthor()
    {
        $this->updateQuoteStateFromNewUser();

        $versionsCount = $this->quote->versions()->count();
        $this->assertGreaterThan(0, $versionsCount);
    }

    /**
     * Test no Version creating when version author is editing a Quote.
     *
     * @return void
     */
    public function testVersionCreatingByAuthor()
    {
        $state = $this->makeGenericQuoteAttributes();
        $state['quote_id'] = $this->quote->id;

        $this->postJson(url('api/quotes/state'), $state, $this->authorizationHeader);

        $versionsCount = $this->quote->versions()->count();
        $this->assertEquals(0, $versionsCount);
    }

    /**
     * Test Versions creating from different causers.
     *
     * @return void
     */
    public function testVersionCreatingFromDifferentCausers()
    {
        $expectedVersionsCount = 3;

        collect()->times($expectedVersionsCount)->each(fn () => $this->updateQuoteStateFromNewUser());

        $actualVersionsCount = $this->quote->versions()->count();

        $this->assertEquals($expectedVersionsCount, $actualVersionsCount);
    }

    public function testVersionSet()
    {
        $this->updateQuoteStateFromNewUser();

        $nonUsingVersion = $this->quote->versionsSelection->firstWhere('is_using', '===', false);
        $version_id = $nonUsingVersion['id'];

        $response = $this->patchJson(url("api/quotes/version/{$this->quote->id}"), compact('version_id'), $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson([true]);

        $actualUsingVersion = $this->quote->load('usingVersion')->usingVersion;

        $this->assertEquals($version_id, $actualUsingVersion->id);
    }

    protected function createAuthorizationHeaderForNewUser(): array
    {
        $user = $this->createUser();
        $token = $this->createAccessToken($user);

        return ['Authorization' => "Bearer {$token}"];
    }

    protected function updateQuoteStateFromNewUser(): void
    {
        $state = $this->makeGenericQuoteAttributes();
        $state['quote_id'] = $this->quote->id;

        $authorizationHeader = $this->createAuthorizationHeaderForNewUser();

        $this->postJson(url('api/quotes/state'), $state, $authorizationHeader);
    }
}
