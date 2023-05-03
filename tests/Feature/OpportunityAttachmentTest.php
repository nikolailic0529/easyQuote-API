<?php

namespace Tests\Feature;

use App\Domain\Attachment\Models\Attachment;
use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group build
 * @group opportunity
 */
class OpportunityAttachmentTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to view a list of the existing attachments of opportunity.
     */
    public function testCanViewListOfOpportunityAttachments(): void
    {
        /** @var \App\Domain\Worldwide\Models\Opportunity $opp */
        $opp = Opportunity::factory()->create();

        $attachments = factory(Attachment::class, 2)->create();

        $opp->attachments()->sync($attachments);

        $this->authenticateApi();

        $response = $this->getJson('api/opportunities/'.$opp->getKey().'/attachments')
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
                        'permissions' => [
                            'update',
                            'delete',
                        ],
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
     * Test an ability to create a new attachment for opportunity.
     */
    public function testCanCreateNewAttachmentForOpportunity(): void
    {
        /** @var Opportunity $opp */
        $opp = Opportunity::factory()->create();

        $file = UploadedFile::fake()->create(Str::random(40).'.txt', 1_000);

        $this->authenticateApi();

        $response = $this->postJson('api/opportunities/'.$opp->getKey().'/attachments', [
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

        $response = $this->getJson('api/opportunities/'.$opp->getKey().'/attachments')
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

        $this->assertContains($attachmentID, $response->json('data.*.id'));
    }

    public function testCanNotCreateAnAttachmentWithTheSameContentForOpportunity(): void
    {
        /** @var Opportunity $opp */
        $opp = Opportunity::factory()->create();

        $file = UploadedFile::fake()->create(Str::random(40).'.txt', 1_000);

        $this->authenticateApi();

        $this->postJson('api/opportunities/'.$opp->getKey().'/attachments', [
            'type' => 'Maintenance Contract',
            'file' => $file,
        ])
//            ->dump()
            ->assertCreated();

        $this->postJson('api/opportunities/'.$opp->getKey().'/attachments', [
            'type' => 'Maintenance Contract',
            'file' => $file,
        ])
//            ->dump()
            ->assertInvalid('file', responseKey: 'Error.original');
    }

    /**
     * Test an ability to delete an existing attachment from opportunity.
     */
    public function testCanDeleteAttachmentFromOpportunity(): void
    {
        /** @var \App\Domain\Worldwide\Models\Opportunity $opp */
        $opp = Opportunity::factory()->create();

        $attachment = factory(Attachment::class)->create();

        $opp->attachments()->attach($attachment);

        $this->authenticateApi();

        $response = $this->getJson('api/opportunities/'.$opp->getKey().'/attachments')
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

        $this->deleteJson('api/opportunities/'.$opp->getKey().'/attachments/'.$attachment->getKey())
            ->assertNoContent();

        $response = $this->getJson('api/opportunities/'.$opp->getKey().'/attachments')
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

        $this->assertEmpty($response->json('data'));
    }
}
