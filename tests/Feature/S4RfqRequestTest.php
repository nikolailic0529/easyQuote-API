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
 * @group client-credentials
 * @group s4
 */
class S4RfqRequestTest extends TestCase
{
    use WithClientCredentials, DatabaseTransactions;

    /**
     * Test an ability to request an existing active submitted quote by RFQ number.
     *
     * @return void
     */
    public function testCanRequestExistingActiveSubmittedQuote(): void
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

    /**
     * Test an ability to request a deleted active submitted quote by RFQ number.
     */
    public function testCanNotRequestDeletedActiveSubmittedQuote(): void
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
     * Test an ability to request an existing active drafted quote by RFQ number.
     *
     * @return void
     */
    public function testCanNotRequestExistingActiveDraftedQuote(): void
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
     * Test an ability to request an existing inactive drafted quote by RFQ number.
     *
     * @return void
     */
    public function testCanNotRequestExistingInactiveDraftedQuote(): void
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
     * Test an ability to request an existing inactive submitted quote by RFQ number.
     *
     * @return void
     */
    public function testCanNotRequestExistingInactiveSubmittedQuote(): void
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
     * Test an ability to request price list file of an existing active submitted quote by RFQ number.
     *
     * @return void
     */
    public function testCanRequestPriceListFileOfExistingActiveSubmittedQuote()
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
