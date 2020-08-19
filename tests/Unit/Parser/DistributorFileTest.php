<?php

namespace Tests\Unit\Parser;

use App\Contracts\Services\PdfParserInterface;
use App\Imports\ImportExcel;
use App\Models\Quote\Quote;
use App\Models\QuoteFile\DataSelectSeparator;
use App\Models\QuoteFile\QuoteFile;
use App\Models\QuoteFile\QuoteFileFormat;
use App\Models\User;
use File;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;

class DistributorFileTest extends TestCase
{
    public function testSuppInba1Year()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/SUPP-INBA_1 year.pdf');

        $pagesContent = $this->pdfParser()->getText($filePath, false);

        // static::storeText($filePath, $pagesContent);

        $result = $this->pdfParser()->parse($pagesContent);

        $pagesResult = $result['pages'];

        $pagesWithRows = collect($pagesResult)->filter(fn ($page) => filled(array_filter($page['rows'])))->pluck('page');

        $pagesContainLines = [3, 4, 5, 6, 7, 8, 9, 10];

        $pagesWithRows->each(fn ($number) => $this->assertContains($number, $pagesContainLines));
    }

    public function testSuppInba2Years()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/SUPP-INBA_2 years.pdf');

        $pagesContent = $this->pdfParser()->getText($filePath, false);

        $result = $this->pdfParser()->parse($pagesContent);

        $pagesResult = $result['pages'];

        $lines = collect($pagesResult)->pluck('rows')->collapse();

        $this->assertArrayHasEqualValues($lines[0], [
            'product_no'    => '818208-B21',
            'description'   => 'HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr',
            'serial_no'     => 'CZJ8170VHN',
            'date_from'     => null,
            'date_to'       => null,
            'qty'           => '1',
            'price'         => '55.00',
            '_one_pay'      => false
        ]);

        $this->assertArrayHasEqualValues($lines[1], [
            'product_no'    => '818208-B21',
            'description'   => 'HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr',
            'serial_no'     => 'CZJ8170VHT',
            'date_from'     => null,
            'date_to'       => null,
            'qty'           => '1',
            'price'         => '55.00',
            '_one_pay'      => false
        ]);

        $this->assertArrayHasEqualValues($lines[2], [
            'product_no'    => '818208-B21',
            'description'   => 'HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr',
            'serial_no'     => 'CZJ8170VHN',
            'date_from'     => null,
            'date_to'       => null,
            'qty'           => '1',
            'price'         => '5.00',
            '_one_pay'      => false
        ]);

        $this->assertArrayHasEqualValues($lines[3], [
            'product_no'    => '818208-B21',
            'description'   => 'HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr',
            'serial_no'     => 'CZJ8170VHT',
            'date_from'     => null,
            'date_to'       => null,
            'qty'           => '1',
            'price'         => '5.00',
            '_one_pay'      => false
        ]);

        $this->assertArrayHasEqualValues($lines[4], [
            'product_no'    => 'UJ558AC',
            'description'   => 'HPE Ind Std Svrs Return to HW Supp',
            'serial_no'     => null,
            'date_from'     => '16.09.2020',
            'date_to'       => null,
            'qty'           => null,
            'price'         => '1,963.40',
            '_one_pay'      => true
        ]);

        $this->assertArrayHasEqualValues($lines[5], [
            'product_no'    => '818208-B21',
            'description'   => 'HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr',
            'serial_no'     => 'CZJ6500J18',
            'date_from'     => null,
            'date_to'       => null,
            'qty'           => '1',
            'price'         => '51.07',
            '_one_pay'      => false
        ]);

        $this->assertArrayHasEqualValues($lines[6], [
            'product_no'    => '818208-B21',
            'description'   => 'HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr',
            'serial_no'     => 'CZJ6290690',
            'date_from'     => null,
            'date_to'       => null,
            'qty'           => '1',
            'price'         => '51.07',
            '_one_pay'      => false
        ]);

        $this->assertArrayHasEqualValues($lines[7], [
            'product_no'    => '818208-B21',
            'description'   => 'HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr',
            'serial_no'     => 'CZJ6500J18',
            'date_from'     => null,
            'date_to'       => null,
            'qty'           => '1',
            'price'         => '4.57',
            '_one_pay'      => false
        ]);

        $this->assertArrayHasEqualValues($lines[8], [
            'product_no'    => '818208-B21',
            'description'   => 'HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr',
            'serial_no'     => 'CZJ6290690',
            'date_from'     => null,
            'date_to'       => null,
            'qty'           => '1',
            'price'         => '4.57',
            '_one_pay'      => false
        ]);

        $this->assertArrayHasEqualValues($lines[9], [
            'product_no'    => 'UJ558AC',
            'description'   => 'HPE Ind Std Svrs Return to HW Supp',
            'serial_no'     => null,
            'date_from'     => '16.09.2020',
            'date_to'       => null,
            'qty'           => null,
            'price'         => '837.48',
            '_one_pay'      => true
        ]);

        $this->assertArrayHasEqualValues($lines[10], [
            'product_no'    => '818208-B21',
            'description'   => 'HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr',
            'serial_no'     => 'CZJ70303XZ',
            'date_from'     => null,
            'date_to'       => null,
            'qty'           => '1',
            'price'         => '60.35',
            '_one_pay'      => false
        ]);

        $this->assertArrayHasEqualValues($lines[11], [
            'product_no'    => '818208-B21',
            'description'   => 'HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr',
            'serial_no'     => 'CZJ70303Y9',
            'date_from'     => null,
            'date_to'       => null,
            'qty'           => '1',
            'price'         => '60.35',
            '_one_pay'      => false
        ]);

        $this->assertArrayHasEqualValues($lines[12], [
            'product_no'    => '818208-B21',
            'description'   => 'HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr',
            'serial_no'     => 'CZJ70303XZ',
            'date_from'     => null,
            'date_to'       => null,
            'qty'           => '1',
            'price'         => '5.71',
            '_one_pay'      => false
        ]);

        $this->assertArrayHasEqualValues($lines[13], [
            'product_no'    => '818208-B21',
            'description'   => 'HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr',
            'serial_no'     => 'CZJ70303Y9',
            'date_from'     => null,
            'date_to'       => null,
            'qty'           => '1',
            'price'         => '5.71',
            '_one_pay'      => false
        ]);

        $this->assertArrayHasEqualValues($lines[14], [
            'product_no'    => 'UJ558AC',
            'description'   => 'HPE Ind Std Svrs Return to HW Supp',
            'serial_no'     => null,
            'date_from'     => '16.09.2020',
            'date_to'       => null,
            'qty'           => null,
            'price'         => '635.58',
            '_one_pay'      => true
        ]);

        $this->assertArrayHasEqualValues($lines[15], [
            'product_no'    => '818208-B21',
            'description'   => 'HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr',
            'serial_no'     => 'CZJ6510640',
            'date_from'     => null,
            'date_to'       => null,
            'qty'           => '1',
            'price'         => '48.78',
            '_one_pay'      => false
        ]);

        $this->assertArrayHasEqualValues($lines[16], [
            'product_no'    => '818208-B21',
            'description'   => 'HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr',
            'serial_no'     => 'CZJ6510645',
            'date_from'     => null,
            'date_to'       => null,
            'qty'           => '1',
            'price'         => '48.78',
            '_one_pay'      => false
        ]);

        $this->assertArrayHasEqualValues($lines[17], [
            'product_no'    => '818208-B21',
            'description'   => 'HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr',
            'serial_no'     => 'CZJ6510640',
            'date_from'     => null,
            'date_to'       => null,
            'qty'           => '1',
            'price'         => '4.57',
            '_one_pay'      => false
        ]);

        $this->assertArrayHasEqualValues($lines[18], [
            'product_no'    => '818208-B21',
            'description'   => 'HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr',
            'serial_no'     => 'CZJ6510645',
            'date_from'     => null,
            'date_to'       => null,
            'qty'           => '1',
            'price'         => '4.57',
            '_one_pay'      => false
        ]);

        $this->assertArrayHasEqualValues($lines[19], [
            'product_no'    => 'UJ558AC',
            'description'   => 'HPE Ind Std Svrs Return to HW Supp',
            'serial_no'     => null,
            'date_from'     => '16.09.2020',
            'date_to'       => null,
            'qty'           => null,
            'price'         => '569.57',
            '_one_pay'      => true
        ]);

        // 818208-B21              HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr                      CZJ8170VHN                                                             1  55.00
        // 818208-B21              HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr                      CZJ8170VHT                                                             1  55.00
        // 818208-B21              HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr                      CZJ8170VHN                                                             1  5.00
        // 818208-B21              HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr                      CZJ8170VHT                                                             1  5.00
        // UJ558AC                 HPE Ind Std Svrs Return to HW Supp                                                                                        16.09.2020       1,963.40
        // 818208-B21              HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr                      CZJ6500J18                                                             1  51.07
        // 818208-B21              HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr                      CZJ6290690                                                             1  51.07
        // 818208-B21              HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr                      CZJ6500J18                                                             1  4.57
        // 818208-B21              HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr                      CZJ6290690                                                             1  4.57
        // UJ558AC                 HPE Ind Std Svrs Return to HW Supp                                                                                        16.09.2020       837.48
        
        // 818208-B21              HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr                      CZJ70303XZ                                                          1    60.35
        // 818208-B21              HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr                      CZJ70303Y9                                                          1    60.35
        
        // 818208-B21              HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr                      CZJ70303XZ                                                          1    5.71
        // 818208-B21              HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr                      CZJ6510640                                                             1  48.78
        // 818208-B21              HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr                      CZJ6510645                                                             1  48.78
        
        // 818208-B21              HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr                      CZJ70303Y9                                                          1    5.71
        // 818208-B21              HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr                      CZJ6510640                                                             1  4.57
        // UJ558AC                 HPE Ind Std Svrs Return to HW Supp                                                                                        16.09.2020       569.57
        // 818208-B21              HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr                      CZJ6510645                                                             1  4.57

        // static::storeText($filePath, $pagesContent);
    }

    public function testSupportWarehouseTataTrygDL380G92()
    {
        $filepath = base_path('tests/Unit/Data/distributor-files-test/SupportWarehouse_TATA_Tryg_DL380G9-2.pdf');

        $parser = $this->pdfParser();

        $pagesContent = $parser->getText($filepath, false);

        // file_put_contents(base_path('tests/Unit/Data/distributor-files-test/SupportWarehouse_TATA_Tryg_DL380G9-2_1.txt'), $pagesContent[0]['content']);
        // file_put_contents(base_path('tests/Unit/Data/distributor-files-test/SupportWarehouse_TATA_Tryg_DL380G9-2_2.txt'), $pagesContent[1]['content']);

        $result = $parser->parse($pagesContent);

        $pagesResult = $result['pages'];

        $this->assertArrayHasEqualValues($pagesResult[0]['rows'][0], [
            'product_no' => '719064-B21',
            'description' => 'HPE DL380 Gen9 8SFF CTO Server',
            'serial_no' => 'CZJ7240DB8',
            'qty' => '1',
            'price' => '10.121,11'
        ]);

        $this->assertArrayHasEqualValues($pagesResult[0]['rows'][1], [
            'product_no' => '719064-B21',
            'description' => 'HPE DL380 Gen9 8SFF CTO Server',
            'serial_no' => 'CZJ7240DB8',
            'qty' => '1',
            'price' => '610,15'
        ]);

        $this->assertArrayHasEqualValues($pagesResult[1]['rows'][0], [
            'product_no' => 'UJ558AC',
            'description' => 'HPE Ind Std Svrs Return to HW Supp',
            'date_from' => '31.08.2020',
            'serial_no' => null,
            'qty' => null,
            'price' => '650,91'
        ]);
    }

    public function testSurwareAdsNc()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/SUPWARE-ADS - NC.pdf');

        $pagesContent = $this->pdfParser()->getText($filePath, false);

        // static::storeText($filePath, $pagesContent);
        
        $result = $this->pdfParser()->parse($pagesContent);

        /**
         * The sixh page contain lines without serial number.
         * This case must be handled.
         */
        $sixthPage = Arr::first($result['pages'] ?? [], fn ($text) => $text['page'] === 6, []);


        $this->assertArrayHasEqualValues($sixthPage['rows'][0], [
            'product_no' => 'BC745B',
            'description' => 'HP 3PAR 7200 OS Suite Base LTU',
            'serial_no' => null,
            'qty' => '1',
            'price' => '28.01'
        ]);

        $this->assertArrayHasEqualValues($sixthPage['rows'][1], [
            'product_no' => 'BC746A',
            'description' => 'HP 3PAR 7200 OS Suite Drive LTU',
            'serial_no' => null,
            'qty' => '12',
            'price' => '8.40'
        ]);

        $this->assertArrayHasEqualValues($sixthPage['rows'][2], [
            'product_no' => 'BC745B',
            'description' => 'HP 3PAR 7200 OS Suite Base LTU',
            'serial_no' => null,
            'qty' => '1',
            'price' => '28.01'
        ]);

        $this->assertArrayHasEqualValues($sixthPage['rows'][3], [
            'product_no' => 'BC746A',
            'description' => 'HP 3PAR 7200 OS Suite Drive LTU',
            'serial_no' => null,
            'qty' => '12',
            'price' => '8.40'
        ]);
    }

    public function test317052SupportWarehouseLtdPhlexglobalL()
    {
        $this->be(factory(User::class)->create(), 'api');

        $quote = factory(Quote::class)->create();

        $quoteFile = $quote->quoteFiles()->create([
            'original_file_path' => Str::random(),
            'original_file_name' => Str::random(),
            'file_type' => 'Distributor Price List',
            'pages' => 2,
            'quote_file_format_id' => QuoteFileFormat::value('id'),
            'imported_page' => 2
        ]);

        $filePath = base_path('tests/Unit/Data/distributor-files-test/317052-Support Warehouse Ltd-Phlexglobal L.xlsx');

        (new ImportExcel($quoteFile))->import($filePath);

        $this->assertEquals(22, $quote->rowsData()->count());
    }

    // public function test81T321172303201922092019LeNbOffre280220191023Tepr()
    // {
    //     $filePath = base_path('tests/Unit/Data/distributor-files-test/81-T32117 23.03.2019 - 22.09.2019, le Nb offre 28.02.2019 1023 [TePr].pdf');

    //     $pagesContent = $this->pdfParser()->getText($filePath, false);

    //     static::storeText($filePath, $pagesContent);
        
    //     // $result = $this->pdfParser()->parse($pagesContent);
    // } 

    protected function pdfParser(): PdfParserInterface
    {
        return app(PdfParserInterface::class);
    }

    protected static function storeText(string $filePath, array $raw)
    {
        $fileName = Str::slug(File::name($filePath), '_');

        foreach ($raw as $text) {
            $pagePath = base_path(sprintf('tests/Unit/Data/distributor-files-test/%s_%s.txt', $fileName, $text['page']));

            file_put_contents($pagePath, $text['content']);
        }
    }
}