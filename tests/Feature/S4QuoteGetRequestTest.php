<?php

namespace Tests\Feature;

use App\Models\Quote\Quote;
use App\Models\QuoteFile\QuoteFile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Tests\TestCase;
use Tests\Unit\Traits\{WithClientCredentials, WithFakeQuote, WithFakeQuoteFile, WithFakeUser};
use function factory;
use function now;

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
    public function testRequestWithSubmittedActiveQuote(): void
    {
        $quoteFile = factory(QuoteFile::class)->create();

        /** @var Quote $quote */
        $quote = factory(Quote::class)->create([
            'submitted_at' => now(),
            'activated_at' => now(),
            'distributor_file_id' => $quoteFile->getKey()
        ]);

        $this->getJson("/api/s4/quotes/".$quote->customer->rfq, $this->clientAuthHeader)
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

    public function testRequestWithSubmittedActiveDeletedQuote(): void
    {
        /** @var Quote $quote */
        $quote = factory(Quote::class)->create([
            'submitted_at' => now(),
            'activated_at' => now(),
        ]);

        $quote->delete();

        $this->getJson("/api/s4/quotes/".$quote->customer->rfq, $this->clientAuthHeader)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Test Request by RFQ Response when Quote is drafted & active.
     *
     * @return void
     */
    public function testRequestWithDraftedActiveQuote(): void
    {
        /** @var Quote $quote */
        $quote = factory(Quote::class)->create([
            'submitted_at' => null,
            'activated_at' => now(),
        ]);

        $quote->delete();

        $this->getJson("/api/s4/quotes/".$quote->customer->rfq, $this->clientAuthHeader)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Test Request by RFQ Response when Quote is drafted & inactive.
     *
     * @return void
     */
    public function testRequestWithDraftedInactiveQuote(): void
    {
        /** @var Quote $quote */
        $quote = factory(Quote::class)->create([
            'submitted_at' => null,
            'activated_at' => null,
        ]);

        $this->getJson("/api/s4/quotes/".$quote->customer->rfq, $this->clientAuthHeader)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Test Request by RFQ Response when Quote is submitted & inactive.
     *
     * @return void
     */
    public function testRequestWithSubmittedInactiveQuote(): void
    {
        /** @var Quote $quote */
        $quote = factory(Quote::class)->create([
            'submitted_at' => now(),
            'activated_at' => null,
        ]);

        $quote->activated_at = null;
        $quote->save();

        $this->getJson("/api/s4/quotes/".$quote->customer->rfq, $this->clientAuthHeader)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Test Request by RFQ Distributor PDF File.
     *
     * @return void
     */
    public function testRequestQuoteDistributorPDFFileByRFQ()
    {
        $quoteFile = factory(QuoteFile::class)->create();

        /** @var Quote $quote */
        $quote = factory(Quote::class)->create([
            'submitted_at' => now(),
            'activated_at' => now(),
            'distributor_file_id' => $quoteFile->getKey()
        ]);

        $this->get("/api/s4/quotes/{$quote->customer->rfq}/pdf", $this->clientAuthHeader)
            ->assertOk()
            ->assertHeader('content-disposition')
            ->assertHeader('content-type', 'application/pdf');
    }
}
