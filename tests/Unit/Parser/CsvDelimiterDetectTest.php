<?php

namespace Tests\Unit\Parser;

use Tests\TestCase;
use App\Contracts\Services\CsvParserInterface;
use Illuminate\Support\Facades\File;

class CsvDelimiterDetectTest extends TestCase
{
    /**
     * CsvParser singleton.
     *
     * @var \App\Contracts\Services\CsvParserInterface
     */
    protected $csv;

    /**
     * Path to files directory.
     *
     * @var string
     */
    protected $filesDirectory = 'tests/Unit/Parser/data/csv';

    protected function setUp(): void
    {
        parent::setUp();

        $this->csv = app(CsvParserInterface::class);
    }

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

        $this->assertIsString($this->csv->guessDelimiter($filepath));
    }

    protected function assertDeterminedDelimiter(string $delimiter, ?string $directory = null): void
    {
        $directory = $directory ?? $delimiter;

        $files = File::files(base_path($this->filesDirectory . DIRECTORY_SEPARATOR . $directory));

        collect($files)->each(function ($file) use ($delimiter) {
            $determinedDelimiter = $this->csv->guessDelimiter($file->getPathname());
            $this->assertEquals($delimiter, $determinedDelimiter);
        });
    }
}
