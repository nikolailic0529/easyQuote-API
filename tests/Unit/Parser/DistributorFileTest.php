<?php

namespace Tests\Unit\Parser;

use App\Contracts\Services\PdfParserInterface;
use File;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;

class DistributorFileTest extends TestCase
{
    public function testSupportWarehouseTataTrygDL380G92()
    {
        $filepath = base_path('tests/Unit/Data/distributor-files-test/SupportWarehouse_TATA_Tryg_DL380G9-2.pdf');

        $parser = $this->parser();

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

        $pagesContent = $this->parser()->getText($filePath, false);

        // static::storeText($filePath, $pagesContent);
        
        $result = $this->parser()->parse($pagesContent);

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

    // public function test81T321172303201922092019LeNbOffre280220191023Tepr()
    // {
    //     $filePath = base_path('tests/Unit/Data/distributor-files-test/81-T32117 23.03.2019 - 22.09.2019, le Nb offre 28.02.2019 1023 [TePr].pdf');

    //     $pagesContent = $this->parser()->getText($filePath, false);

    //     static::storeText($filePath, $pagesContent);
        
    //     // $result = $this->parser()->parse($pagesContent);
    // } 

    protected function parser(): PdfParserInterface
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