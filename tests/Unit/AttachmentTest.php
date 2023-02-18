<?php

namespace Tests\Unit;

use App\Domain\Attachment\Models\Attachment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * @group build
 */
class AttachmentTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to create a new attachment.
     *
     * @return void
     */
    public function testCanCreateNewAttachment()
    {
        $this->authenticateApi();

        $attributes = factory(Attachment::class)->state('file')->raw();

        $response = $this->postJson('api/attachments', $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'type',
                'filepath',
                'filename',
                'extension',
                'size',
                'created_at',
            ]);

        $filepath = $response->json('filepath');
        $filename = File::basename($filepath);

        Storage::disk('attachments')->assertExists($filename);
    }

    /**
     * Test an ability to download an existing attachment.
     *
     * @return void
     */
    public function testCanDownloadExistingAttachment()
    {
        $this->authenticateApi();

        $attributes = factory(Attachment::class)->state('file')->raw();

        $response = $this->postJson('api/attachments', $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'type',
                'filepath',
                'filename',
                'extension',
                'size',
                'created_at',
            ]);

        $attachmentID = $response->json('id');
        $attachmentFileName = $response->json('filename');

        $this->get('api/attachments/'.$attachmentID.'/download')
//            ->dumpHeaders()
            ->assertOk()
            ->assertHeader('content-disposition', "attachment; filename=$attachmentFileName");
    }
}
