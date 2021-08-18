<?php

namespace Tests\Unit\Parser;

use App\Models\QuoteFile\QuoteFile;
use App\Models\QuoteFile\QuoteFileFormat;
use App\Services\DocumentEngine\ParserClientFactory;
use App\Services\DocumentProcessor\DocumentEngine\DeExcelPriceListProcessor;
use App\Services\DocumentProcessor\DocumentEngine\DePdfRescuePaymentScheduleProcessor;
use App\Services\DocumentProcessor\DocumentEngine\DePdfRescuePriceListProcessor;
use App\Services\DocumentProcessor\DocumentEngine\PriceListResponseDataMapper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ReflectionClass;
use Tests\TestCase;
use Webpatser\Uuid\Uuid;

/**
 * @group build
 * @group document-engine-impl
 */
class DocumentEngineTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test performs parsing of pdf price list file using document engine api.
     *
     * @return void
     * @throws \App\Services\Exceptions\FileException
     */
    public function testItPerformsParsingOfPdfPriceListUsingDocumentEngine()
    {
        /** @var ParserClientFactory $parserFactory */
        $parserFactory = $this->app[ParserClientFactory::class];

        $response = $parserFactory->buildRescuePdfPriceListParser()
            ->filePath(base_path('tests/Unit/Data/distributor-files-test/SUPP-INBA_1 year.pdf'))
            ->firstPage(3)
            ->lastPage(3)
            ->process();

        $this->assertIsArray($response);

        $this->assertArrayHasKey('attributes', $response[0]);
        $this->assertArrayHasKey('pricing_document', $response[0]['attributes']);
        $this->assertArrayHasKey('system_handle', $response[0]['attributes']);
        $this->assertArrayHasKey('service_agreement_id', $response[0]['attributes']);

        $this->assertArrayHasKey('header', $response[0]);
        $this->assertArrayHasKey('rows', $response[0]);

        $this->assertCount(5, $response[0]['rows']);
    }

    /**
     * Test performs parsing of excel price list using document engine api.
     *
     * @return void
     * @throws \App\Services\Exceptions\FileException
     */
    public function testItPerformsParsingOfExcelPriceListUsingDocumentEngine()
    {
        /** @var ParserClientFactory $parserFactory */
        $parserFactory = $this->app[ParserClientFactory::class];

        $response = $parserFactory->buildGenericExcelPriceListParser()
            ->filePath(base_path('tests/Unit/Data/distributor-files-test/SupportWarehouse - Kromann Reumert.xlsx'))
            ->process();

        $this->assertIsArray($response);
        $this->assertNotEmpty($response);

        $this->assertArrayHasKey('header', $response[0]);
        $this->assertIsArray($response[0]['header']);
        $this->assertNotEmpty($response[0]['header']);

        $this->assertArrayHasKey('rows', $response[0]);
        $this->assertNotEmpty($response[0]['rows']);
        $this->assertIsArray($response[0]['rows']);
    }

    /**
     * Test processes excel price list quote file using document engine.
     *
     * @return void
     * @throws \App\Services\DocumentProcessor\Exceptions\NoDataFoundException
     * @throws \App\Services\Exceptions\FileException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testItProcessesExcelPriceListQuoteFileUsingDocumentEngine()
    {
        $storage = Storage::fake();

        $filePath = base_path('tests/Unit/Data/distributor-files-test/SupportWarehouse - Kromann Reumert.xlsx');

        $storage->put($storageFilePath = Str::random(40).'.xlsx', file_get_contents($filePath));

        /** @var QuoteFile $quoteFile */
        $quoteFile = tap(new QuoteFile(), function (QuoteFile $quoteFile) use ($storageFilePath, $filePath) {
            $quoteFile->{$quoteFile->getKeyName()} = (string)Uuid::generate(4);
            $quoteFile->original_file_name = basename($filePath);
            $quoteFile->original_file_path = $storageFilePath;
            $quoteFile->imported_page = 1;
            $quoteFile->file_type = 'Distributor Price List';
            $quoteFile->format()->associate(
                QuoteFileFormat::query()->where('extension', 'xlsx')->first()
            );

            $quoteFile->save();
        });

        /** @var DeExcelPriceListProcessor $processor */
        $processor = $this->app->make(DeExcelPriceListProcessor::class);

        $processor->process($quoteFile);

        $this->assertNotEmpty($quoteFile->rowsData->all());
    }

    /**
     *
     * Test processes distributor file and updates quote file rows.
     *
     * @return void
     */
    public function testItDoesNotFailOnUnexpectedDocumentEngineResponse()
    {
//        $this->markTestSkipped();

        $storage = Storage::persistentFake();

        $fileName = Str::random(40).'.pdf';

        $storage->put($fileName, file_get_contents(base_path('tests/Unit/Data/distributor-files-test/wmhFl0YtLIwxQdYc6W3a0M6UsSUQKjed53iZQoAb.pdf')));

//            $storage->put($fileName, file_get_contents(base_path('tests/Unit/Data/distributor-files-test/SUPP-INBA_1 year.pdf')));

        $quoteFile = factory(QuoteFile::class)->create([
            'original_file_path' => $fileName,
        ]);

        /** @var DePdfRescuePriceListProcessor $processor */
        $processor = $this->app->make(DePdfRescuePriceListProcessor::class);

        $processor->process($quoteFile);

        $this->assertTrue(true);
    }

    /**
     * Test performs parsing of pdf payment schedule using document engine api.
     *
     * @return void
     * @throws \App\Services\Exceptions\FileException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testItPerformsParsingOfPdfPaymentScheduleUsingDocumentEngine()
    {
//        $this->markTestSkipped();

        /** @var ParserClientFactory $parserFactory */
        $parserFactory = $this->app[ParserClientFactory::class];

        $response = $parserFactory->buildGenericPdfPaymentScheduleParser()
            ->filePath(base_path('tests/Unit/Data/schedule-files-test/France/Billing summary (with partner discount) 81-T31870 Nb offre 21.01.2020 - 31.03.2022  [Purchase].pdf'))
            ->page(1)
            ->process();

        $this->assertIsArray($response);

        $this->assertCount(3, $response);

        foreach (static::$paymentResponse[1] as $payment) {
            $this->assertContainsEquals($payment, $response);
        }
    }

    /**
     * Test Distributor Response mapping.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    public function testMapsDistributorFileResponse()
    {
        /** @var DePdfRescuePriceListProcessor */
        $parser = $this->app->make(PriceListResponseDataMapper::class);

        $class = new ReflectionClass($parser);
        $method = $class->getMethod('mapDistributorResponse');
        $method->setAccessible(true);

        $currentPage = 1;

        $quoteFile = (new QuoteFile)->forceFill([
            'id' => (string)Uuid::generate(4),
            'imported_page' => $currentPage,
        ]);

        $mappedRows = $method->invokeArgs($parser, [$quoteFile, static::$distrResponse]);

        foreach (static::$distrResponse as $page) {
            ['header' => $header, 'rows' => $rows, 'attributes' => $attributes] = $page;

            if ($rows === null) {
                $this->assertFalse(collect($mappedRows)->contains('page', $currentPage));

                $currentPage++;
                continue;
            }

            $mappedPageRows = collect($mappedRows)->where('page', $currentPage);

            $this->assertCount(count($rows), $mappedPageRows);

            $rowsColumns = $mappedPageRows->map(function ($row) {
                $columns = json_decode($row['columns_data'], true);

                return collect($columns)->map->value->values()->all();
            });

            $rows = array_map(fn($row) => $row + [
                    'system_handle' => Arr::get($attributes, 'system_handle'),
                    'pricing_document' => Arr::get($attributes, 'pricing_document'),
                    'searchable' => Arr::get($attributes, 'service_agreement_id'),
                ], $rows);

            foreach ($rows as $key => $row) {
                $this->assertContainsEquals(array_values($row), $rowsColumns);
            }

            $currentPage++;
        }

        $onePayLine = Arr::pull($mappedRows, 3);

        $this->assertTrue($onePayLine['is_one_pay']);

        foreach ($mappedRows as $row) {
            $this->assertFalse($row['is_one_pay']);
        }

//        $mappedRows = $method->invokeArgs($parser, [$quoteFile, static::$distrResponse2]);
    }

    /**
     * Test Payment Schedule Response mapping.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    public function testPaymentResponseMapping()
    {
        /** @var DePdfRescuePaymentScheduleProcessor */
        $parser = $this->app->make(DePdfRescuePaymentScheduleProcessor::class);

        $class = new ReflectionClass($parser);
        $method = $class->getMethod('mapPaymentResponse');
        $method->setAccessible(true);

        $currentPage = 1;

        $quoteFile = (new QuoteFile)->forceFill([
            'id' => (string)Uuid::generate(4),
            'imported_page' => $currentPage,
        ]);

        $mappedResponse = $method->invokeArgs($parser, [static::$paymentResponse[0]]);

        $this->assertCount(count(static::$paymentResponse[0]), $mappedResponse);

        $this->assertEquals([
            [
                "from" => "10.10.20",
                "to" => "09.10.21",
                "price" => "13'620.31",
            ],
            [
                "from" => "10.10.21",
                "to" => "09.10.22",
                "price" => "20'341.03",
            ],
            [
                "from" => "10.10.22",
                "to" => "09.10.23",
                "price" => "23'004.36",
            ],
            [
                "from" => "10.10.23",
                "to" => "31.10.23",
                "price" => "1'341.92",
            ],

        ], $mappedResponse);
    }

    public function testUnprocessablePaymentResponseMapping()
    {
        /** @var DePdfRescuePaymentScheduleProcessor */
        $parser = $this->app->make(DePdfRescuePaymentScheduleProcessor::class);

        $class = new ReflectionClass($parser);
        $method = $class->getMethod('mapPaymentResponse');
        $method->setAccessible(true);

        $currentPage = 1;

        $quoteFile = (new QuoteFile)->forceFill([
            'id' => (string)Uuid::generate(4),
            'imported_page' => $currentPage,
        ]);

        $mappedResponse = $method->invokeArgs($parser, [null]);

        $this->assertEquals([], $mappedResponse);

        $mappedResponse = $method->invokeArgs($parser, [[
            [
                "f" => "10.10.20",
                "t" => "09.10.21",
                "v" => "13'620.31",
            ],
            [
                "f" => "10.10.21",
                "t" => "09.10.22",
                "v" => "20'341.03",
            ],
            [
                "f" => "10.10.22",
                "t" => "09.10.23",
                "v" => "23'004.36",
            ],
            [
                "f" => "10.10.23",
                "t" => "31.10.23",
                "v" => "1'341.92",
            ],
        ]]);

        $this->assertEquals([], $mappedResponse);
    }

    protected static $paymentResponse = [
        [
            [
                "from_date" => "10.10.20",
                "to_date" => "09.10.21",
                "value" => "13'620.31",
            ],
            [
                "from_date" => "10.10.21",
                "to_date" => "09.10.22",
                "value" => "20'341.03",
            ],
            [
                "from_date" => "10.10.22",
                "to_date" => "09.10.23",
                "value" => "23'004.36",
            ],
            [
                "from_date" => "10.10.23",
                "to_date" => "31.10.23",
                "value" => "1'341.92",
            ],
        ],
        [
            [
                "from_date" => "21.01.20",
                "to_date" => "20.01.21",
                "value" => "1'587.60",
            ],
            [
                "from_date" => "21.01.21",
                "to_date" => "20.01.22",
                "value" => "1'587.60",
            ],
            [
                "from_date" => "21.01.22",
                "to_date" => "31.03.22",
                "value" => "308.70",
            ],
        ],
    ];

    protected static $distrResponse = [
        [
            "attributes" => [
                "pricing_document" => "56784797",
                "system_handle" => "SUPPINBA-UK KINGDOM",
                "service_agreement_id" => "1086 5193 2250",
            ],
            "header" => null,
            "rows" => null,
        ],
        [
            "attributes" => [
                "pricing_document" => "56784797",
                "system_handle" => "SUPPINBA-UK KINGDOM",
                "service_agreement_id" => "1086 5193 2250",
            ],
            "header" => [
                "product_no" => "Product No.",
                "description" => "Description",
                "serial_no" => "Serial No.",
                "from" => "from:",
                "coverage_period_to" => "Coverage Period to:",
                "qty" => "Qty",
                "price_gbp" => "Price/GBP",
            ],
            "rows" => [
                [
                    "product_no" => "H7J33AC",
                    "description" => "HPE Foundation Care NBD wDMR SVC",
                    "serial_no" => "",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "",
                ],
                [
                    "product_no" => "661189-B21",
                    "description" => "HP DL360e Gen8 8SFF CTO Server",
                    "serial_no" => "CZ3323FBRL",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "25.89",
                ],
                [
                    "product_no" => "661189-B21",
                    "description" => "HP DL360e Gen8 8SFF CTO Server",
                    "serial_no" => "CZ3323FBRL",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "4.89",
                ],
                [
                    "product_no" => "UJ558AC",
                    "description" => "HPE Ind Std Svrs Return to HW Supp",
                    "serial_no" => "",
                    "from" => "",
                    "coverage_period_to" => "19.06.2019",
                    "qty" => "",
                    "price_gbp" => "1,290.00",
                ],
            ],
        ],
        [
            "attributes" => [
                "pricing_document" => "56784797",
                "system_handle" => "SUPPINBA-UK KINGDOM",
                "service_agreement_id" => "1086 5193 2250",
            ],
            "header" => null,
            "rows" => null,
        ],
        [
            "attributes" => [
                "pricing_document" => "56784797",
                "system_handle" => "SUPPINBA-UK KINGDOM",
                "service_agreement_id" => "1086 5193 2250",
            ],
            "header" => [
                "product_no" => "Product No.",
                "description" => "Description",
                "serial_no" => "Serial No.",
                "from" => "from:",
                "coverage_period_to" => "Coverage Period to:",
                "qty" => "Qty",
                "price_gbp" => "Price/GBP",
            ],
            "rows" => [
                [
                    "product_no" => "H7J33AC",
                    "description" => "HPE Foundation Care NBD wDMR SVC",
                    "serial_no" => "",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "",
                ],
                [
                    "product_no" => "661189-B21",
                    "description" => "HP DL360e Gen8 8SFF CTO Server",
                    "serial_no" => "CZJ302051C",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "33.17",
                ],
                [
                    "product_no" => "661189-B21",
                    "description" => "HP DL360e Gen8 8SFF CTO Server",
                    "serial_no" => "CZJ302051C",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "5.87",
                ],
            ],
        ],
        [
            "attributes" => [
                "pricing_document" => "56784797",
                "system_handle" => "SUPPINBA-UK KINGDOM",
                "service_agreement_id" => "1086 5193 2250",
            ],
            "header" => null,
            "rows" => null,
        ],
        [
            "attributes" => [
                "pricing_document" => "56784797",
                "system_handle" => "SUPPINBA-UK KINGDOM",
                "service_agreement_id" => "1086 5193 2250",
            ],
            "header" => [
                "product_no" => "Product No.",
                "description" => "Description",
                "serial_no" => "Serial No.",
                "from" => "from:",
                "coverage_period_to" => "Coverage Period to:",
                "qty" => "Qty",
                "price_gbp" => "Price/GBP",
            ],
            "rows" => [
                [
                    "product_no" => "H7J33AC",
                    "description" => "HPE Foundation Care NBD wDMR SVC",
                    "serial_no" => "",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "",
                ],
                [
                    "product_no" => "661189-B21",
                    "description" => "HP DL360e Gen8 8SFF CTO Server",
                    "serial_no" => "CZJ3020539",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "42.14",
                ],
                [
                    "product_no" => "661189-B21",
                    "description" => "HP DL360e Gen8 8SFF CTO Server",
                    "serial_no" => "CZJ3020539",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "6.02",
                ],
            ],
        ],
    ];

    protected static $distrResponse2 = [
        [
            "attributes" => [
                "pricing_document" => null,
                "system_handle" => "SUPPINBA-UK KINGDOM",
                "service_agreement_id" => "1086 5193 2250",
            ],
            "header" => null,
            "rows" => null,
        ],
        [
            "attributes" => [
                "pricing_document" => null,
                "system_handle" => null,
                "service_agreement_id" => null,
            ],
            "header" => [
                "product_no" => "Product No.",
                "description" => "Description",
                "serial_no" => "Serial No.",
                "from" => "from:",
                "coverage_period_to" => "Coverage Period to:",
                "qty" => "Qty",
                "price_gbp" => "Price/GBP",
            ],
            "rows" => [
                [
                    "product_no" => "H7J33AC",
                    "description" => "HPE Foundation Care NBD wDMR SVC",
                    "serial_no" => "",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "",
                ],
                [
                    "product_no" => "661189-B21",
                    "description" => "HP DL360e Gen8 8SFF CTO Server",
                    "serial_no" => "CZ3323FBRL",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "25.89",
                ],
                [
                    "product_no" => "661189-B21",
                    "description" => "HP DL360e Gen8 8SFF CTO Server",
                    "serial_no" => "CZ3323FBRL",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "4.89",
                ],
                [
                    "product_no" => "UJ558AC",
                    "description" => "HPE Ind Std Svrs Return to HW Supp",
                    "serial_no" => "",
                    "from" => "",
                    "coverage_period_to" => "19.06.2019",
                    "qty" => "",
                    "price_gbp" => "1,290.00",
                ],
            ],
        ],
        [
            "attributes" => [
                "pricing_document" => null,
                "system_handle" => "SUPPINBA-UK KINGDOM",
                "service_agreement_id" => "1086 5193 2250",
            ],
            "header" => null,
            "rows" => null,
        ],
        [
            "attributes" => [
                "pricing_document" => null,
                "system_handle" => "SUPPINBA-UK KINGDOM",
                "service_agreement_id" => "1086 5193 2250",
            ],
            "header" => [
                "product_no" => "Product No.",
                "description" => "Description",
                "serial_no" => "Serial No.",
                "from" => "from:",
                "coverage_period_to" => "Coverage Period to:",
                "qty" => "Qty",
                "price_gbp" => "Price/GBP",
            ],
            "rows" => [
                [
                    "product_no" => "H7J33AC",
                    "description" => "HPE Foundation Care NBD wDMR SVC",
                    "serial_no" => "",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "",
                ],
                [
                    "product_no" => "661189-B21",
                    "description" => "HP DL360e Gen8 8SFF CTO Server",
                    "serial_no" => "CZJ302051C",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "33.17",
                ],
                [
                    "product_no" => "661189-B21",
                    "description" => "HP DL360e Gen8 8SFF CTO Server",
                    "serial_no" => "CZJ302051C",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "5.87",
                ],
            ],
        ],
        [
            "attributes" => [
                "pricing_document" => null,
                "system_handle" => "SUPPINBA-UK KINGDOM",
                "service_agreement_id" => "1086 5193 2250",
            ],
            "header" => null,
            "rows" => null,
        ],
        [
            "attributes" => [
                "pricing_document" => null,
                "system_handle" => "SUPPINBA-UK KINGDOM",
                "service_agreement_id" => "1086 5193 2250",
            ],
            "header" => [
                "product_no" => "Product No.",
                "description" => "Description",
                "serial_no" => "Serial No.",
                "from" => "from:",
                "coverage_period_to" => "Coverage Period to:",
                "qty" => "Qty",
                "price_gbp" => "Price/GBP",
            ],
            "rows" => [
                [
                    "product_no" => "H7J33AC",
                    "description" => "HPE Foundation Care NBD wDMR SVC",
                    "serial_no" => "",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "",
                ],
                [
                    "product_no" => "661189-B21",
                    "description" => "HP DL360e Gen8 8SFF CTO Server",
                    "serial_no" => "CZJ3020539",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "42.14",
                ],
                [
                    "product_no" => "661189-B21",
                    "description" => "HP DL360e Gen8 8SFF CTO Server",
                    "serial_no" => "CZJ3020539",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "6.02",
                ],
            ],
        ],
    ];
}
