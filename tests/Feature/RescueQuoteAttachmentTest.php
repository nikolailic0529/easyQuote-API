<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Quote\Quote;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group build
 */
class RescueQuoteAttachmentTest extends TestCase
{
    /**
     * Test an ability to view a list of the existing attachments of quote.
     */
    public function testCanViewListOfAttachmentsOfQuote(): void
    {
        /** @var Quote $quote */
        $quote = factory(Quote::class)->create();

        $attachments = factory(Attachment::class, 2)->create();

        $quote->attachments()->sync($attachments);

        $this->authenticateApi();

        $response = $this->getJson('api/quotes/'.$quote->getKey().'/attachments')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user',
                        'type',
                        'parent_entity_type',
                        'filepath',
                        'filename',
                        'extension',
                        'size',
                        'created_at',
                    ],
                ],
            ]);

        $response->assertJsonCount(2, 'data');

        foreach ($attachments as $attachment) {

            $this->assertContains($attachment->getKey(), $response->json('data.*.id'));

        }
    }

    /**
     * Test an ability to create a new attachment for quote.
     */
    public function testCanCreateNewAttachmentForQuote(): void
    {
        /** @var Quote $quote */
        $quote = factory(Quote::class)->create();

        $file = UploadedFile::fake()->create(Str::random(40).'.txt', 1_000);

        $this->authenticateApi();

        $response = $this->postJson('api/quotes/'.$quote->getKey().'/attachments', [
            'type' => 'Maintenance Contract',
            'file' => $file,
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'user',
                'type',
                'filepath',
                'filename',
                'size',
                'created_at',
            ]);

        $attachmentID = $response->json('id');

        $response = $this->getJson('api/quotes/'.$quote->getKey().'/attachments')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user',
                        'type',
                        'filepath',
                        'filename',
                        'extension',
                        'size',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertContains($attachmentID, $response->json('data.*.id'));
    }

    /**
     * Test an ability to delete an existing attachment of quote.
     */
    public function testCanDeleteAttachmentOfQuote(): void
    {
        /** @var Quote $quote */
        $quote = factory(Quote::class)->create();

        $attachment = factory(Attachment::class)->create();

        $quote->attachments()->attach($attachment);

        $this->authenticateApi();

        $response = $this->getJson('api/quotes/'.$quote->getKey().'/attachments')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user',
                        'type',
                        'filepath',
                        'filename',
                        'extension',
                        'size',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertContains($attachment->getKey(), $response->json('data.*.id'));

        $this->deleteJson('api/quotes/'.$quote->getKey().'/attachments/'.$attachment->getKey())
            ->assertNoContent();

        $response = $this->getJson('api/quotes/'.$quote->getKey().'/attachments')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'filepath',
                        'filename',
                        'extension',
                        'size',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertEmpty($response->json('data'));
    }
}
