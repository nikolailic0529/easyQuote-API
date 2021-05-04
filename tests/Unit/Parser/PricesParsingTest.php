<?php

namespace Tests\Unit\Parser;

use App\Models\QuoteFile\QuoteFile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;

/**
 * @group build
 */
class PricesParsingTest extends ParsingTest
{
    use DatabaseTransactions;

    /**
     * Test Belgium Prices Processing.
     *
     * @return void
     */
    public function testBelgiumPricesProcessing()
    {
        $this->processFilesByCountry('Belgium');
    }

    /**
     * Test Canada Prices Processing.
     *
     * @return void
     */
    public function testCanadaPricesProcessing()
    {
        $this->processFilesByCountry('Canada');
    }

    /**
     * Test Denmark Prices Processing.
     *
     * @return void
     */
    public function testDenmarkPricesProcessing()
    {
        $this->processFilesByCountry('Denmark');
    }

    /**
     * Test France Prices Processing.
     *
     * @return void
     */
    public function testFrancePricesProcessing()
    {
        $this->markTestSkipped();
        $this->processFilesByCountry('France');
    }

    /**
     * Test Ireland Prices Processing.
     *
     * @return void
     */
    public function testIrelandPricesProcessing()
    {
        $this->markTestSkipped();
        $this->processFilesByCountry('Ireland');
    }

    /**
     * Test United Kingdom Prices Processing.
     *
     * @return void
     */
    public function testUKpricesProcessing()
    {
        // $this->markTestSkipped();
        $this->processFilesByCountry('UK');
    }

    /**
     * Test United States Prices Processing.
     *
     * @return void
     */
    public function testUSApricesProcessing()
    {$this->markTestSkipped();
        $this->processFilesByCountry('USA');
    }

    protected function filesType(): string
    {
        return QFT_PL;
    }

    protected function filesDirPath(): string
    {
        return 'tests/Unit/Data/distributor-files-test';
    }

    protected function performFileAssertions(QuoteFile $quoteFile): void
    {
        $expectedRowsCount = $this->getMappingAttribute('count', $quoteFile->original_file_name);

        $this->assertEquals($expectedRowsCount, $quoteFile->rowsData()->count(), $this->message($quoteFile));
    }

    protected function mapping(): Collection
    {
        return collect(json_decode(file_get_contents('tests/Unit/Data/distributor-files-test/mapping.json'), true));
    }
}
