<?php

namespace Tests\Feature;

use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\Rescue\Models\Quote;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * @group build
 * @group client-credentials
 * @group s4
 */
class S4RfqRequestTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to request an existing active submitted quote by RFQ number.
     */
    public function testCanRequestExistingActiveSubmittedQuote(): void
    {
        $this->authenticateAsClient();

        $quoteFile = \factory(QuoteFile::class)->create();

        /** @var \App\Domain\Rescue\Models\Quote $quote */
        $quote = \factory(Quote::class)->create([
            'submitted_at' => \now(),
            'activated_at' => \now(),
            'distributor_file_id' => $quoteFile->getKey(),
        ]);

        $this->getJson('/api/s4/quotes/'.$quote->customer->rfq)
            ->assertOk()
            ->assertJsonStructure([
                'price_list_file',
                'pdf_file',
                'quote_data' => [
                    'first_page',
                    'data_pages',
                    'last_page',
                    'payment_schedule',
                ],
            ]);
    }

    /**
     * Test an ability to request a deleted active submitted quote by RFQ number.
     */
    public function testCanNotRequestDeletedActiveSubmittedQuote(): void
    {
        $this->authenticateAsClient();

        /** @var Quote $quote */
        $quote = \factory(Quote::class)->create([
            'submitted_at' => \now(),
            'activated_at' => \now(),
        ]);

        $quote->delete();

        $this->getJson('/api/s4/quotes/'.$quote->customer->rfq)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Test an ability to request an existing active drafted quote by RFQ number.
     */
    public function testCanNotRequestExistingActiveDraftedQuote(): void
    {
        $this->authenticateAsClient();

        /** @var Quote $quote */
        $quote = \factory(Quote::class)->create([
            'submitted_at' => null,
            'activated_at' => \now(),
        ]);

        $quote->delete();

        $this->getJson('/api/s4/quotes/'.$quote->customer->rfq)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Test an ability to request an existing inactive drafted quote by RFQ number.
     */
    public function testCanNotRequestExistingInactiveDraftedQuote(): void
    {
        $this->authenticateAsClient();

        /** @var Quote $quote */
        $quote = \factory(Quote::class)->create([
            'submitted_at' => null,
            'activated_at' => null,
        ]);

        $this->getJson('/api/s4/quotes/'.$quote->customer->rfq)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Test an ability to request an existing inactive submitted quote by RFQ number.
     */
    public function testCanNotRequestExistingInactiveSubmittedQuote(): void
    {
        $this->authenticateAsClient();

        /** @var Quote $quote */
        $quote = \factory(Quote::class)->create([
            'submitted_at' => \now(),
            'activated_at' => null,
        ]);

        $quote->activated_at = null;
        $quote->save();

        $this->getJson('/api/s4/quotes/'.$quote->customer->rfq)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Test an ability to request price list file of an existing active submitted quote by RFQ number.
     *
     * @return void
     */
    public function testCanRequestPriceListFileOfExistingActiveSubmittedQuote()
    {
        $this->authenticateAsClient();

        $quoteFile = \factory(QuoteFile::class)->create();

        /** @var Quote $quote */
        $quote = \factory(Quote::class)->create([
            'submitted_at' => \now(),
            'activated_at' => \now(),
            'distributor_file_id' => $quoteFile->getKey(),
        ]);

        $this->get("/api/s4/quotes/{$quote->customer->rfq}/pdf")
            ->assertOk()
            ->assertHeader('content-disposition')
            ->assertHeader('content-type', 'application/pdf');
    }
}
