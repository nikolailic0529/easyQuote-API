<?php

namespace Tests\Unit;

use App\Contracts\Repositories\QuoteFile\QuoteFileRepositoryInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Unit\Traits\FakeQuote;
use Tests\Unit\Traits\FakeUser;

class QuoteS4GetRequestTest extends TestCase
{
    use DatabaseTransactions, WithFaker, FakeUser, FakeQuote;

    protected $quoteFileRepository;

    protected $user;

    protected $quote;

    public function setUp(): void
    {
        parent::setUp();

        $this->quoteFileRepository = app(QuoteFileRepositoryInterface::class);

        $this->user = $this->fakeUser();
        $this->quote = $this->fakeQuote($this->user);
    }

    /**
     * Submit the Quote and check availability using request.
     *
     * @return void
     */
    public function testQuoteRequestByRfq()
    {
        $this->quote->submit();
        $this->quote->refres();

        $this->assertTrue(!is_null($this->quote->submitted_at));
    }
}
