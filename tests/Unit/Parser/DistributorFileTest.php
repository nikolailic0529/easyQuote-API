<?php

namespace Tests\Unit\Parser;

use App\Contracts\Services\PdfParserInterface;
use Tests\TestCase;

class DistributorFileTest extends TestCase
{
    public function testSupportWarehouseTataTrygDL380G92()
    {
        $filepath = base_path('tests/Unit/Data/distributor-files-test/SupportWarehouse_TATA_Tryg_DL380G9-2.pdf');

        /** @var \App\Services\PdfParser\PdfParser */
        $parser = app(PdfParserInterface::class);

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
}