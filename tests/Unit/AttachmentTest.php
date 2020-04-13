<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Unit\Traits\WithFakeUser;
use Illuminate\Support\{
    Facades\Storage,
    Facades\File,
};
use App\Models\Attachment;

class AttachmentTest extends TestCase
{
    use WithFakeUser;
    /**
     * Test attachment store.
     *
     * @return void
     */
    public function testAttachmentStore()
    {
        $attributes = factory(Attachment::class)->state('file')->raw();

        $response = $this->postJson(url('api/attachments'), $attributes)
            ->assertJsonStructure(['id', 'type', 'filepath', 'filename', 'extension', 'size', 'created_at'])
            ->assertOk();

        $filepath = $response->json('filepath');
        $filename = File::basename($filepath);

        Storage::disk('attachments')->assertExists($filename);
    }
}
