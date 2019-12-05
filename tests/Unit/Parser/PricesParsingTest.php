<?php

namespace Tests\Unit\Parser;

use App\Models\QuoteFile\QuoteFile;
use Illuminate\Support\Collection;

class PricesParsingTest extends ParsingTest
{
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
        $this->processFilesByCountry('France');
    }

    /**
     * Test Ireland Prices Processing.
     *
     * @return void
     */
    public function testIrelandPricesProcessing()
    {
        $this->processFilesByCountry('Ireland');
    }

    /**
     * Test United Kingdom Prices Processing.
     *
     * @return void
     */
    public function testUKpricesProcessing()
    {
        $this->processFilesByCountry('UK');
    }

    /**
     * Test United States Prices Processing.
     *
     * @return void
     */
    public function testUSApricesProcessing()
    {
        $this->processFilesByCountry('USA');
    }

    protected function filesType(): string
    {
        return __('quote_file.types.price');
    }

    protected function filesDirPath(): string
    {
        return 'tests/Unit/Parser/data/prices';
    }

    protected function performFileAssertions(QuoteFile $quoteFile): void
    {
        $this->assertEquals('completed', $quoteFile->processing_status, $this->message($quoteFile));

        $expectedRowsCount = $this->getMappingAttribute('count', $quoteFile->original_file_name);
        $this->assertEquals($quoteFile->rowsData()->count(), $expectedRowsCount);
    }

    protected function mapping(): Collection
    {
        return collect(json_decode(file_get_contents('tests/Unit/Parser/data/prices/mapping.json'), true));
    }
}
