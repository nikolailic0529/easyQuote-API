<?php

namespace Tests\Unit\Parser;

use App\Models\QuoteFile\QuoteFile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;

/**
 * @group build
 */
class SchedulesParsingTest extends ParsingTest
{
    use DatabaseTransactions;

    /**
     * Test Austria Payment Schedules Processing.
     *
     * @return void
     */
    public function testAustriaSchedulesProcessing()
    {
        $this->processFilesByCountry('Austria');
    }

    /**
     * Test France Payment Schedules Processing.
     *
     * @return void
     */
    public function testFranceSchedulesProcessing()
    {
        $this->processFilesByCountry('France');
    }

    /**
     * Test United Kingdom Payment Schedules Processing.
     *
     * @return void
     */
    public function testUnitedKingdomSchedulesProcessing()
    {
        $this->processFilesByCountry('UK');
    }

    /**
     * Test United States Payment Schedules Processing.
     *
     * @return void
     */
    public function testUnitedStatesSchedulesProcessing()
    {
        $this->processFilesByCountry('USA');
    }

    /**
     * Test Nederland Payment Schedules Processing.
     *
     * @return void
     */
    public function testNederlandSchedulesProcessing()
    {
        $this->processFilesByCountry('Nederland');
    }

    protected function filesType(): string
    {
        return QFT_PS;
    }

    protected function filesDirPath(): string
    {
        return base_path('tests/Unit/Data/schedule-files-test');
    }

    protected function performFileAssertions(QuoteFile $quoteFile): void
    {
        $this->assertTrue(filled($quoteFile->scheduleData->value), $this->message($quoteFile));

        $map = Collection::wrap($this->getMappingAttribute('data', $quoteFile->original_file_name))
            ->keyBy('from');

        $this->assertInstanceOf(Collection::class, $quoteFile->scheduleData->value);

        $parsedSchedule = Collection::wrap($quoteFile->scheduleData->value);

        $this->assertCount($map->count(), $parsedSchedule);

        $parsedSchedule->each(function ($payment) use ($map) {
            $payMatch = $map->get(Arr::get($payment, 'from'));
            $this->assertEquals($payMatch, $payment);
        });
    }

    protected function mapping(): Collection
    {
        return collect(json_decode(file_get_contents('tests/Unit/Data/schedule-files-test/mapping.json'), true));
    }
}
