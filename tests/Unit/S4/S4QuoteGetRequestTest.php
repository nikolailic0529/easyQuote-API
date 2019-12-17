<?php

namespace Tests\Unit\S4;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\{
    WithFakeUser,
    WithFakeQuote,
    WithFakeQuoteFile
};
use Illuminate\Foundation\Testing\TestResponse;
use App\Models\Quote\Quote;

class S4QuoteGetRequestTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser, WithFakeQuote, WithFakeQuoteFile;

    /**
     * Submit the Quote and check availability using request.
     *
     * @return void
     */
    public function testRequestWithSubmittedActivatedQuote(): void
    {
        $this->importQuoteFile();
        $this->submitQuote($this->quote);
        $this->activateQuote($this->quote);

        $response = $this->requestQuoteByRfq($this->quote);

        $response->assertJsonStructure([
            'price_list_file',
            'pdf_file',
            'quote_data' => [
                'first_page',
                'data_pages',
                'last_page',
                'payment_schedule'
            ]
        ]);
    }

    public function testRequestWithSubmittedActivatedDeletedQuote(): void
    {
        $this->importQuoteFile();
        $this->submitQuote($this->quote);
        $this->activateQuote($this->quote);
        $this->deleteQuote($this->quote);

        $response = $this->requestQuoteByRfq($this->quote);

        $this->assertQuoteNotFoundResponse($response);
    }

    /**
     * Test Request by RFQ Response when Quote was unsubmitted & activated.
     *
     * @return void
     */
    public function testRequestWithUnSubmittedActivatedQuote(): void
    {
        $this->importQuoteFile();
        $this->unSubmitQuote($this->quote);
        $this->activateQuote($this->quote);

        $response = $this->requestQuoteByRfq($this->quote);

        $this->assertQuoteNotFoundResponse($response);
    }

    /**
     * Test Request by RFQ Response when Quote was unsubmitted & deactivated.
     *
     * @return void
     */
    public function testRequestWithUnSubmittedDeactivatedQuote(): void
    {
        $this->importQuoteFile();
        $this->unSubmitQuote($this->quote);
        $this->deactivateQuote($this->quote);

        $response = $this->requestQuoteByRfq($this->quote);

        $this->assertQuoteNotFoundResponse($response);
    }

    /**
     * * Test Request by RFQ Response when Quote was submitted & deactivated.
     *
     * @return void
     */
    public function testRequestWithSubmittedDeactivatedQuote(): void
    {
        $this->importQuoteFile();
        $this->submitQuote($this->quote);
        $this->deactivateQuote($this->quote);

        $response = $this->requestQuoteByRfq($this->quote);

        $this->assertQuoteNotFoundResponse($response);
    }

    protected function assertQuoteNotFoundResponse(TestResponse $response): void
    {
        $response->assertJson([
            'message' => 'Quote not found for the provided RFQ #',
            'code' => 'EQ_NF_01'
        ]);
    }

    protected function requestQuoteByRfq(Quote $quote): TestResponse
    {
        return $this->getJson(url("/api/s4/quotes/{$quote->customer->rfq}"));
    }

    protected function importQuoteFile(): void
    {
        $quoteFile = $this->createQuoteFile('tests/Unit/Parser/data/prices/UK/HPEUK.pdf', $this->quote);

        $this->parser->routeParser($quoteFile);
    }

    protected function submitQuote(Quote $quote): void
    {
        $this->quoteRepository->submit($quote);
        $quote->refresh();

        $this->assertNotNull($quote->submitted_at, 'Fail when submitting a Quote');
    }

    protected function unSubmitQuote(Quote $quote): void
    {
        $quote->unSubmit();
        $quote->refresh();

        $this->assertNull($quote->submitted_at, 'Fail when unsubmitting a Quote');
    }

    protected function activateQuote(Quote $quote): void
    {
        $quote->activate();
        $quote->refresh();

        $this->assertNotNull($quote->activated_at, 'Fail when activating a Quote');
    }

    protected function deactivateQuote(Quote $quote): void
    {
        $quote->deactivate();
        $quote->refresh();

        $this->assertNull($quote->activated_at, 'Fail when deactivating a Quote');
    }

    protected function deleteQuote(Quote $quote): void
    {
        $quote->delete();
        $quote->refresh();

        $this->assertNotNull($quote->deleted_at, 'Fail when deleting a Quote');
    }
}
