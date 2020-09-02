<?php

namespace Tests\Unit\Parser;

use App\Contracts\Services\{PdfParserInterface, WordParserInterface};
use App\Imports\ImportExcel;
use App\Models\{User, Quote\Quote, QuoteFile\QuoteFileFormat};
use Illuminate\Support\{Arr, Str};
use Tests\TestCase;
use File;

class DistributorFileTest extends TestCase
{

    public function testSupportWarehouseKromannReumenrtXlsx()
    {
        $this->be(factory(User::class)->create(), 'api');

        /** @var Quote */
        $quote = factory(Quote::class)->create();

        $quoteFile = $quote->quoteFiles()->create([
            'original_file_path'   => Str::random(),
            'original_file_name'   => Str::random(),
            'file_type'            => 'Distributor Price List',
            'pages'                => 2,
            'quote_file_format_id' => QuoteFileFormat::value('id'),
            'imported_page'        => 1
        ]);

        $filePath = base_path('tests/Unit/Data/distributor-files-test/SupportWarehouse - Kromann Reumert.xlsx');

        (new ImportExcel($quoteFile))->import($filePath);

        $importedRows = $quoteFile->rowsData->pluck('columns_data')->map(fn ($row) => collect($row)->pluck('value', 'header')->all())->all();

        $this->assertCount(7, $importedRows);
    }

    public function testSupportWarehouseLtdSelectAdministrativeServices4969805508272020xlsx()
    {
        // Support Warehouse Ltd-SELECT ADMINISTRATIVE SERVICES-49698055-08272020.xlsx

        $this->be(factory(User::class)->create(), 'api');

        /** @var Quote */
        $quote = factory(Quote::class)->create();

        $quoteFile = $quote->quoteFiles()->create([
            'original_file_path'   => Str::random(),
            'original_file_name'   => Str::random(),
            'file_type'            => 'Distributor Price List',
            'pages'                => 2,
            'quote_file_format_id' => QuoteFileFormat::value('id'),
            'imported_page'        => 1
        ]);

        $filePath = base_path('tests/Unit/Data/distributor-files-test/Support Warehouse Ltd-SELECT ADMINISTRATIVE SERVICES-49698055-08272020.xlsx');

        (new ImportExcel($quoteFile))->import($filePath);

        $assertRows = [
            [
                'Qty'                  => 1,
                'Monthly List Price'   => 100,
                'Description'          => 'HP DL360 Gen9 E5-2630v3 SAS Reman Svr',
                'Reseller cost'        => 461.9306000000001,
                'Coverage Period From' => '11/01/2020',
                'Line Item Price'      => 563.33,
                'Serial No.'           => '2M41539PTP',
                'Product No.'          => '755262R-B21',
                'Coverage Period To'   => NULL,
            ],
            [
                'Qty'                  => 1,
                'Monthly List Price'   => 100,
                'Description'          => 'HPE DL360 Gen9 E5-2630v3 Base SAS Svr',
                'Reseller cost'        => 492.00000000000006,
                'Coverage Period From' => NULL,
                'Line Item Price'      => 600,
                'Serial No.'           => 'MXQ52805JL',
                'Product No.'          => '755262-B21',
                'Coverage Period To'   => NULL,
            ],
            [
                'Qty'                  => 1,
                'Monthly List Price'   => 133,
                'Description'          => 'HP DL380 Gen9 E5-2640v3 US Svr/S-Buy',
                'Reseller cost'        => 654.36,
                'Coverage Period From' => NULL,
                'Line Item Price'      => 798,
                'Serial No.'           => 'MXQ54006RP',
                'Product No.'          => '777338-S01',
                'Coverage Period To'   => NULL,
            ],
            [
                'Qty'                  => 1,
                'Monthly List Price'   => 100,
                'Description'          => 'HPE DL360 Gen9 E5-2630v3 Base SAS Svr',
                'Reseller cost'        => 492.00000000000006,
                'Coverage Period From' => NULL,
                'Line Item Price'      => 600,
                'Serial No.'           => 'MXQ5420101',
                'Product No.'          => '755262-B21',
                'Coverage Period To'   => NULL,
            ],
            [
                'Qty'                  => 1,
                'Monthly List Price'   => 5,
                'Description'          => 'HPE DL360 G9 E5-2609v3 SAS US Svr/S-Buy',
                'Reseller cost'        => 24.4606,
                'Coverage Period From' => '10/21/2020',
                'Line Item Price'      => 29.83,
                'Serial No.'           => 'MXQ53804YL',
                'Product No.'          => '780017-S01',
                'Coverage Period To'   => NULL,
            ],
            [
                'Qty'                  => 1,
                'Monthly List Price'   => 7,
                'Description'          => 'HP DL380 Gen9 E5-2640v3 US Svr/S-Buy',
                'Reseller cost'        => 34.440000000000005,
                'Coverage Period From' => NULL,
                'Line Item Price'      => 42,
                'Serial No.'           => 'MXQ54006RP',
                'Product No.'          => '777338-S01',
                'Coverage Period To'   => NULL,
            ],
            [
                'Qty'                  => 1,
                'Monthly List Price'   => 6,
                'Description'          => 'HP DL360 Gen9 E5-2630v3 SAS Reman Svr',
                'Reseller cost'        => 27.716,
                'Coverage Period From' => '11/01/2020',
                'Line Item Price'      => 33.8,
                'Serial No.'           => '2M41539PTP',
                'Product No.'          => '755262R-B21',
                'Coverage Period To'   => NULL,
            ],
            [
                'Qty'                  => 1,
                'Monthly List Price'   => 6,
                'Description'          => 'HPE DL360 Gen9 E5-2630v3 Base SAS Svr',
                'Reseller cost'        => 29.520000000000003,
                'Coverage Period From' => NULL,
                'Line Item Price'      => 36,
                'Serial No.'           => 'MXQ52805JL',
                'Product No.'          => '755262-B21',
                'Coverage Period To'   => NULL,
            ],
            [
                'Qty'                  => 1,
                'Monthly List Price'   => 100,
                'Description'          => 'HPE DL360 G9 E5-2609v3 SAS US Svr/S-Buy',
                'Reseller cost'        => 489.2694,
                'Coverage Period From' => '10/21/2020',
                'Line Item Price'      => 596.67,
                'Serial No.'           => 'MXQ53804YL',
                'Product No.'          => '780017-S01',
                'Coverage Period To'   => NULL,
            ],
            [
                'Qty'                  => 1,
                'Monthly List Price'   => 6,
                'Description'          => 'HPE DL360 Gen9 E5-2630v3 Base SAS Svr',
                'Reseller cost'        => 29.520000000000003,
                'Coverage Period From' => NULL,
                'Line Item Price'      => 36,
                'Serial No.'           => 'MXQ5420101',
                'Product No.'          => '755262-B21',
                'Coverage Period To'   => NULL,
            ],
        ];

        $importedRows = $quoteFile->rowsData->pluck('columns_data')->map(fn ($row) => collect($row)->pluck('value', 'header')->all())->all();

        foreach ($assertRows as $row) {
            $this->assertContainsEquals($row, $importedRows);
        }
    }

    public function testRenewalSupportWarehouseVanBaelBellisFc24x7docx()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/Renewal Support Warehouse Van Bael  Bellis FC 24x7.docx');

        $pagesResult = $this->wordParser()->getText($filePath, false);

        $this->assertIsArray($pagesResult);

        $lines = preg_split('/\n/', $pagesResult[0]['content']);

        array_shift($lines);

        $rows = collect($lines)->map(fn ($line) => array_map(fn ($value) => filled($value) ? $value : null, preg_split('/\t/', $line)));

        // $this->assertArrayHasEqualValues(
        //     $rows[0],
        //     [
        //         "719064-B21",
        //         "HPE DL380 Gen9 8SFF CTO Server",
        //         "CZJ60408ZP",
        //         "1",
        //         "128,00",
        //         null,
        //         null,
        //         null,
        //     ]
        // );

        // $this->assertArrayHasEqualValues(
        //     $rows[1],
        //     [
        //         "719064-B21",
        //         "HPE DL380 Gen9 8SFF CTO Server",
        //         "CZJ60408ZN",
        //         "1",
        //         "128,00",
        //         null,
        //         null,
        //         null,
        //     ]
        // );

        // $this->assertArrayHasEqualValues(
        //     $rows[2],
        //     [
        //         "719064-B21",
        //         "HPE DL380 Gen9 8SFF CTO Server",
        //         "CZJ60408ZQ",
        //         "1",
        //         "128,00",
        //         null,
        //         null,
        //         null,
        //     ]
        // );

        // $this->assertArrayHasEqualValues(
        //     $rows[3],
        //     [
        //         "719064-B21",
        //         "HPE DL380 Gen9 8SFF CTO Server",
        //         "CZJ60408XW",
        //         "1",
        //         "128,00",
        //         null,
        //         null,
        //         null,
        //     ]
        // );

        // $this->assertArrayHasEqualValues(
        //     $rows[4],
        //     [
        //         "677278-421",
        //         "HP DL380p Gen8 E5-2630 Enrgy Star EU Svr",
        //         "CZ22420DVX",
        //         "1",
        //         "135,00",
        //         null,
        //         null,
        //         null,
        //     ]
        // );

        // $this->assertArrayHasEqualValues(
        //     $rows[5],
        //     [
        //         "719064-B21",
        //         "HPE DL380 Gen9 8SFF CTO Server",
        //         "CZJ60408ZP",
        //         "1",
        //         "8,00",
        //         null,
        //         null,
        //         null,
        //     ]
        // );

        // $this->assertArrayHasEqualValues(
        //     $rows[6],
        //     [
        //         "719064-B21",
        //         "HPE DL380 Gen9 8SFF CTO Server",
        //         "CZJ60408ZN",
        //         "1",
        //         "8,00",
        //         null,
        //         null,
        //         null,
        //     ]
        // );

        // $this->assertArrayHasEqualValues(
        //     $rows[7],
        //     [
        //         "719064-B21",
        //         "HPE DL380 Gen9 8SFF CTO Server",
        //         "CZJ60408ZQ",
        //         "1",
        //         "8,00",
        //         null,
        //         null,
        //         null,
        //     ]
        // );

        // $this->assertArrayHasEqualValues(
        //     $rows[8],
        //     [
        //         "719064-B21",
        //         "HPE DL380 Gen9 8SFF CTO Server",
        //         "CZJ60408XW",
        //         "1",
        //         "8,00",
        //         null,
        //         null,
        //         null,
        //     ]
        // );

        // $this->assertArrayHasEqualValues(
        //     $rows[9],
        //     [
        //         "677278-421",
        //         "HP DL380p Gen8 E5-2630 Enrgy Star EU Svr",
        //         "CZ22420DVX",
        //         "1",
        //         "7,00",
        //         null,
        //         null,
        //         null,
        //     ]
        // );

        // $this->assertArrayHasEqualValues(
        //     $rows[10],
        //     [
        //         "UJ558AC",
        //         "HPE Ind Std Svrs Return to HW Supp",
        //         "30.09.2020",
        //         "7.235,00",
        //         null,
        //         null,
        //         null,
        //         "1",
        //     ]
        // );

        $filePathCsv = base_path('tests/Unit/Data/distributor-files-test/' . Str::slug('Renewal Support Warehouse Van Bael  Bellis FC 24x7', '-') . '.csv');

        // file_put_contents($filePathCsv, $pagesResult[0]['content']);
    }

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
            'original_file_path'   => Str::random(),
            'original_file_name'   => Str::random(),
            'file_type'            => 'Distributor Price List',
            'pages'                => 2,
            'quote_file_format_id' => QuoteFileFormat::value('id'),
            'imported_page'        => 2
        ]);

        $filePath = base_path('tests/Unit/Data/distributor-files-test/317052-Support Warehouse Ltd-Phlexglobal L.xlsx');

        (new ImportExcel($quoteFile))->import($filePath);

        $this->assertEquals(22, $quote->rowsData()->count());
    }

    public function testCopyOfSupportWarehouseLimitedAlgonquinLakeshore07062020()
    {
        $this->be(factory(User::class)->create(), 'api');

        $filePath = base_path('tests/Unit/Data/distributor-files-test/Copy of SUPPORT WAREHOUSE LIMITED-ALGONQUIN  LAKESHORE-07062020.xlsx');

        /** @var Quote */
        $quote = factory(Quote::class)->create();

        $quoteFile = $quote->quoteFiles()->create([
            'original_file_path'   => $filePath,
            'original_file_name'   => Str::random(),
            'file_type'            => 'Distributor Price List',
            'pages'                => 1,
            'quote_file_format_id' => QuoteFileFormat::whereExtension('xls')->value('id'),
            'imported_page'        => 1
        ]);

        $this->postJson('api/quotes/handle', [
            'page'          => 1,
            'quote_file_id' => $quoteFile->getKey(),
            'quote_id'      => $quote->getKey()
        ])->assertOk();

        /** @var \Illuminate\Support\Collection */
        $rows = $quote->refresh()->getMappedRows();

        $rows = $rows->map(fn ($row) => Arr::except((array) $row, ['id', 'replicated_row_id']))->toArray();

        array_multisort($rows);

        $this->assertSame($rows, [
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '582633-B21',
                'date_to'       => '30/04/2021',
                'description'   => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price'         => '21.10',
                'serial_no'     => null,
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '582633-B21',
                'date_to'       => '30/04/2021',
                'description'   => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price'         => '21.10',
                'serial_no'     => null,
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '582633-B21',
                'date_to'       => '30/04/2021',
                'description'   => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price'         => '21.10',
                'serial_no'     => null,
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '582633-B21',
                'date_to'       => '30/04/2021',
                'description'   => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price'         => '21.10',
                'serial_no'     => null,
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '582633-B21',
                'date_to'       => '30/04/2021',
                'description'   => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price'         => '21.10',
                'serial_no'     => null,
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '582633-B21',
                'date_to'       => '30/04/2021',
                'description'   => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price'         => '21.10',
                'serial_no'     => null,
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '582633-B21',
                'date_to'       => '30/04/2021',
                'description'   => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price'         => '49.23',
                'serial_no'     => null,
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '582633-B21',
                'date_to'       => '30/04/2021',
                'description'   => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price'         => '49.23',
                'serial_no'     => null,
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '582633-B21',
                'date_to'       => '30/04/2021',
                'description'   => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price'         => '49.23',
                'serial_no'     => null,
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '582633-B21',
                'date_to'       => '30/04/2021',
                'description'   => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price'         => '49.23',
                'serial_no'     => null,
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '582633-B21',
                'date_to'       => '30/04/2021',
                'description'   => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price'         => '49.23',
                'serial_no'     => null,
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '582633-B21',
                'date_to'       => '30/04/2021',
                'description'   => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price'         => '49.23',
                'serial_no'     => null,
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '670633-S01',
                'date_to'       => '30/09/2021',
                'description'   => 'HP DL360p Gen8 S-Buy E5-2620 Base US Svr',
                'price'         => '84.93',
                'serial_no'     => 'MXQ3300CHR',
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '670633-S01',
                'date_to'       => '30/09/2021',
                'description'   => 'HP DL360p Gen8 S-Buy E5-2620 Base US Svr',
                'price'         => '849.33',
                'serial_no'     => 'MXQ3300CHR',
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '670853-S01',
                'date_to'       => '30/09/2021',
                'description'   => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price'         => '97.07',
                'serial_no'     => '2M231601DK',
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '670853-S01',
                'date_to'       => '30/09/2021',
                'description'   => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price'         => '97.07',
                'serial_no'     => '2M231601DN',
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '670853-S01',
                'date_to'       => '30/09/2021',
                'description'   => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price'         => '97.07',
                'serial_no'     => '2M233402QL',
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '670853-S01',
                'date_to'       => '30/09/2021',
                'description'   => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price'         => '97.07',
                'serial_no'     => '2M233402QN',
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '670853-S01',
                'date_to'       => '30/09/2021',
                'description'   => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price'         => '97.07',
                'serial_no'     => '2M241301ZP',
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '670853-S01',
                'date_to'       => '30/09/2021',
                'description'   => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price'         => '97.07',
                'serial_no'     => '2M241301ZQ',
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '670853-S01',
                'date_to'       => '30/09/2021',
                'description'   => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price'         => '1092.00',
                'serial_no'     => '2M231601DK',
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '670853-S01',
                'date_to'       => '30/09/2021',
                'description'   => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price'         => '1092.00',
                'serial_no'     => '2M231601DN',
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '670853-S01',
                'date_to'       => '30/09/2021',
                'description'   => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price'         => '1092.00',
                'serial_no'     => '2M233402QL',
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '670853-S01',
                'date_to'       => '30/09/2021',
                'description'   => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price'         => '1092.00',
                'serial_no'     => '2M233402QN',
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '670853-S01',
                'date_to'       => '30/09/2021',
                'description'   => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price'         => '1092.00',
                'serial_no'     => '2M241301ZP',
            ],
            [
                'is_selected'   => 0,
                'group_name'    => null,
                'date_from'     => '01/10/2020',
                'qty'           => 1,
                'product_no'    => '670853-S01',
                'date_to'       => '30/09/2021',
                'description'   => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price'         => '1092.00',
                'serial_no'     => '2M241301ZQ',
            ]
        ]);
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

    protected function wordParser(): WordParserInterface
    {
        return app(WordParserInterface::class);
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
