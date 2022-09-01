<?php

namespace Tests\Unit\Parser;

use App\Models\QuoteFile\QuoteFile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

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
        $this->processFilesFromDir('Austria');
    }

    /**
     * Test France Payment Schedules Processing.
     *
     * @return void
     */
    public function testFranceSchedulesProcessing()
    {
        $this->processFilesFromDir('France');
    }

    /**
     * Test United Kingdom Payment Schedules Processing.
     *
     * @return void
     */
    public function testUnitedKingdomSchedulesProcessing()
    {
        $this->processFilesFromDir('UK');
    }

    /**
     * Test United States Payment Schedules Processing.
     *
     * @return void
     */
    public function testUnitedStatesSchedulesProcessing()
    {
        $this->processFilesFromDir('USA');
    }

    /**
     * Test Nederland Payment Schedules Processing.
     *
     * @return void
     */
    public function testNederlandSchedulesProcessing()
    {
        $this->processFilesFromDir('Nederland');
    }

    protected function fileType(): string
    {
        return QFT_PS;
    }

    protected function filesDirPath(): string
    {
        return base_path('tests/Unit/Data/schedule-files-test');
    }

    protected function performFileAssertions(QuoteFile $quoteFile): void
    {
        $this->assertTrue(filled($quoteFile->scheduleData->value), $quoteFile->original_file_path);

        $map = Collection::wrap($this->resolveAttributeFromAssertMapping('data', $quoteFile->original_file_name))
            ->keyBy('from');

        $this->assertInstanceOf(Collection::class, $quoteFile->scheduleData->value);

        $parsedSchedule = Collection::wrap($quoteFile->scheduleData->value);

        $this->assertCount($map->count(), $parsedSchedule);

        $parsedSchedule->each(function ($payment) use ($map) {
            $payMatch = $map->get(Arr::get($payment, 'from'));
            $this->assertEquals($payMatch, $payment);
        });
    }

    protected function assertionMapping(): Collection
    {
        return collect(json_decode(file_get_contents(base_path('tests/Unit/Data/schedule-files-test/mapping.json')), true));
    }
}
