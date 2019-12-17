<?php

namespace Tests\Unit\Parser;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tests\Unit\Traits\WithFakeUser;
use Illuminate\Http\Testing\File as TestingFile;
use Setting;

class UploadQuoteFileTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser;

    /**
     * Test QuoteFile Storing.
     *
     * @return void
     */
    public function testUploadSupportedQuoteFile(): void
    {
        $priceLists = collect(File::allFiles('tests/Unit/Parser/data/prices'))
            ->filter(function ($file) {
                $extension = strtoupper($file->getExtension());

                return isset(array_flip(Setting::get('supported_file_types'))[$extension]);
            });

        $priceList = $priceLists->random();

        $file = UploadedFile::fake()->createWithContent($priceList->getRealPath(), File::get($priceList->getRealPath()));

        $response = $this->uploadFile($file);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'id',
            'file_type',
            'pages',
            'original_file_path',
            'original_file_name',
            'user_id',
            'quote_file_format_id'
        ]);
    }

    public function testUploadNonSupportedQuoteFile(): void
    {
        $file = UploadedFile::fake()->create('nonsupported.extension', 64);

        $response = $this->uploadFile($file);

        $response->assertStatus(422);

        $response->assertJsonStructure([
            'message', 'errors' => ['quote_file']
        ]);
    }

    public function testUploadQuoteFileLargerThanAllowedSize()
    {
        $file = UploadedFile::fake()->create('large_file.csv', Setting::get('file_upload_size_kb') * 2);

        $response = $this->uploadFile($file);

        $response->assertStatus(422);

        $response->assertJsonStructure([
            'message', 'errors' => ['quote_file']
        ]);
    }

    protected function uploadFile(TestingFile $file, ?string $type = null)
    {
        return $this->postJson(
            url('/api/quotes/file'),
            ['quote_file' => $file, 'file_type' => $type ?? __('quote_file.types.price')],
            ['Authorization' => "Bearer {$this->accessToken}"]
        );
    }
}