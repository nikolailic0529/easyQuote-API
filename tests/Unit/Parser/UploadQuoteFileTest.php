<?php

namespace Tests\Unit\Parser;

use App\Domain\Settings\Facades\Setting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Testing\File as TestingFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * @group build
 */
class UploadQuoteFileTest extends TestCase
{
    use DatabaseTransactions;

    public function testUploadQuoteFileWithInfiniteCoordinatesRange()
    {
        $this->authenticateApi();

        $filePath = base_path('tests/Unit/Data/distributor-files-test/Support Warehouse Ltd-SELECT ADMINISTRATIVE SERVICES-49698055-08272020.xlsx');

        $file = UploadedFile::fake()->createWithContent($filePath, File::get($filePath));

        $response = $this->uploadFile($file)->assertCreated();

        $this->assertEquals(3, $response->json('pages'));
    }

    /**
     * Test QuoteFile Storing.
     */
    public function testUploadSupportedQuoteFile(): void
    {
        $this->authenticateApi();

        $file = UploadedFile::fake()->createWithContent(base_path('tests/Unit/Data/distributor-files-test/HPInvent1547101.pdf'), File::get(base_path('tests/Unit/Data/distributor-files-test/HPInvent1547101.pdf')));

        $this->uploadFile($file)
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'file_type',
                'pages',
                'original_file_path',
                'original_file_name',
                'user_id',
                'quote_file_format_id',
            ]);
    }

    public function testUploadNonSupportedQuoteFile(): void
    {
        $this->authenticateApi();

        $file = UploadedFile::fake()->create('nonsupported.extension', 64);

        $response = $this->uploadFile($file);

        $response->assertStatus(422);

        $response->assertJsonStructure([
            'message', 'Error' => ['original' => ['quote_file']],
        ]);
    }

    public function testUploadQuoteFileLargerThanAllowedSize()
    {
        $this->authenticateApi();

        $file = UploadedFile::fake()->create('large_file.csv', Setting::get('file_upload_size_kb') * 2);

        $response = $this->uploadFile($file);

        $response->assertStatus(422);

        $response->assertJsonStructure([
            'message', 'Error' => ['original' => ['quote_file']],
        ]);
    }

    protected function uploadFile(TestingFile $file, ?string $type = null)
    {
        return $this->postJson(
            url('/api/quotes/file'),
            ['quote_file' => $file, 'file_type' => $type ?? QFT_PL],
        );
    }
}
