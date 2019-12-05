<?php

namespace Tests\Unit\Parser;

use App\Models\QuoteFile\QuoteFile;
use Illuminate\Support\Collection;

class SchedulesParsingTest extends ParsingTest
{
    /**
     * Test France Payment Schedules Processing.
     *
     * @return void
     */
    public function testFranceSchedulesParsing()
    {
        $this->processFilesByCountry('France');
    }

    /**
     * Test United Kingdom Payment Schedules Processing.
     *
     * @return void
     */
    public function testUKSchedulesProcessing()
    {
        $this->processFilesByCountry('UK');
    }

    protected function filesType(): string
    {
        return __('quote_file.types.schedule');
    }

    protected function filesDirPath(): string
    {
        return 'tests/Unit/Parser/data/schedules';
    }

    protected function performFileAssertions(QuoteFile $quoteFile): void
    {
        $this->assertEquals('completed', $quoteFile->processing_status, $this->message($quoteFile));
        $this->assertTrue(filled($quoteFile->scheduleData->value));
    }

    protected function mapping(): Collection
    {
        return collect(json_decode(file_get_contents('tests/Unit/Parser/data/schedules/mapping.json'), true));
    }
}
