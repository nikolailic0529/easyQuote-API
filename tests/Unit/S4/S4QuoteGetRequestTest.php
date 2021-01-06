<?php

namespace Tests\Unit\S4;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\{
    WithClientCredentials,
    WithFakeUser,
    WithFakeQuote,
    WithFakeQuoteFile
};
use Illuminate\Testing\TestResponse;
use App\Models\Quote\Quote;

/**
 * @group build
 */
class S4QuoteGetRequestTest extends TestCase
{
    use WithFakeUser, WithFakeQuote, WithFakeQuoteFile, WithClientCredentials, DatabaseTransactions;

    /**
     * Submit the Quote and check availability using request.
     *
     * @return void
     */
    public function testRequestWithSubmittedActivatedQuote(): void
    {
        $quote = $this->createQuote($this->user);
        
        $this->importQuoteFile($quote);
        $this->submitQuote($quote);
        $this->activateQuote($quote);

        $this->requestQuoteByRfq($quote)
            ->assertOk()
            ->assertJsonStructure([
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
        $quote = $this->createQuote($this->user);

        $this->importQuoteFile($quote);
        $this->submitQuote($quote);
        $this->activateQuote($quote);
        $this->deleteQuote($quote);

        $response = $this->requestQuoteByRfq($quote);

        $this->assertQuoteNotFoundResponse($response);
    }

    /**
     * Test Request by RFQ Response when Quote was unsubmitted & activated.
     *
     * @return void
     */
    public function testRequestWithUnSubmittedActivatedQuote(): void
    {
        $quote = $this->createQuote($this->user);

        $this->importQuoteFile($quote);
        $this->unSubmitQuote($quote);
        $this->activateQuote($quote);

        $response = $this->requestQuoteByRfq($quote);

        $this->assertQuoteNotFoundResponse($response);
    }

    /**
     * Test Request by RFQ Response when Quote was unsubmitted & deactivated.
     *
     * @return void
     */
    public function testRequestWithUnSubmittedDeactivatedQuote(): void
    {
        $quote = $this->createQuote($this->user);

        $this->importQuoteFile($quote);
        $this->unSubmitQuote($quote);
        $this->deactivateQuote($quote);

        $response = $this->requestQuoteByRfq($quote);

        $this->assertQuoteNotFoundResponse($response);
    }

    /**
     * Test Request by RFQ Response when Quote was submitted & deactivated.
     *
     * @return void
     */
    public function testRequestWithSubmittedDeactivatedQuote(): void
    {
        $quote = $this->createQuote($this->user);

        $this->importQuoteFile($quote);
        $this->submitQuote($quote);
        $this->deactivateQuote($quote);

        $response = $this->requestQuoteByRfq($quote);

        $this->assertQuoteNotFoundResponse($response);
    }

    /**
     * Test Request by RFQ Distributor PDF File.
     *
     * @return void
     */
    public function testRequestQuoteDistributorPDFFileByRFQ()
    {
        $quote = $this->createQuote($this->user);

        $this->importQuoteFile($quote);
        $this->submitQuote($quote);
        $this->activateQuote($quote);

        $this->get("/api/s4/quotes/{$quote->customer->rfq}/pdf", $this->clientAuthHeader)
            ->assertOk()
            ->assertHeader('content-disposition')
            ->assertHeader('content-type', 'application/pdf');
    }

    protected function assertQuoteNotFoundResponse(TestResponse $response): void
    {
        $response->assertJson([
            'ErrorDetails' => 'Quote not found for the provided RFQ #',
            'ErrorCode' => 'EQ_NF_01'
        ]);
    }

    protected function requestQuoteByRfq(Quote $quote): TestResponse
    {
        return $this->getJson(url("/api/s4/quotes/{$quote->customer->rfq}"), $this->clientAuthHeader);
    }

    protected function importQuoteFile(Quote $quote): void
    {
        $quoteFile = $this->createQuoteFile('tests/Unit/Data/distributor-files-test/UK/HPEUK.pdf', $quote);

        $this->parser->forwardProcessor($quoteFile);
    }

    protected function submitQuote(Quote $quote): void
    {
        $quote->submit();
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
