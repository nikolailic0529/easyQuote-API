<?php

namespace Tests\Unit\Parser;

use App\Contracts\Services\CsvParserInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * @group build
 */
class CsvDelimiterDetectTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Path to files directory.
     *
     * @var string
     */
    protected $filesDirectory = 'tests/Unit/Data/csv-files-test';

    /**
     * Test valid detection colon delimiter.
     *
     * @return void
     */
    public function testDetermineColonDelimiter()
    {
        $this->assertDeterminedDelimiter('colon');
    }

    /**
     * Test valid detection comma delimiter.
     *
     * @return void
     */
    public function testDetermineCommaDelimiter()
    {
        $this->assertDeterminedDelimiter('comma');
    }

    /**
     * Test valid detection tab delimiter.
     *
     * @return void
     */
    public function testDetermineTabDelimiter()
    {
        $this->assertDeterminedDelimiter('tab');
    }

    /**
     * Test valid detection semicolon delimiter.
     *
     * @return void
     */
    public function testDetermineSemicolonDelimiter()
    {
        $this->assertDeterminedDelimiter('semicolon');
    }

    public function testDetermineDelimiterWithOneLine()
    {
        $filepath = base_path("{$this->filesDirectory}/one_line.csv");

        $this->assertIsString($this->app->make(CsvParserInterface::class)->guessDelimiter($filepath));
    }

    protected function assertDeterminedDelimiter(string $delimiter, ?string $directory = null): void
    {
        $directory = $directory ?? $delimiter;

        $files = File::files(base_path($this->filesDirectory . DIRECTORY_SEPARATOR . $directory));

        collect($files)->each(function ($file) use ($delimiter) {
            $determinedDelimiter = $this->app->make(CsvParserInterface::class)->guessDelimiter($file->getPathname());
            $this->assertEquals($delimiter, $determinedDelimiter);
        });
    }
}
