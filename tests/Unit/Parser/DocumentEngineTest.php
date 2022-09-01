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
     * @group document-engine-api-interaction
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
     * @group document-engine-api-interaction
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
     * @group document-engine-api-interaction
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
     * @group document-engine-api-interaction
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
     * @group document-engine-api-interaction
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
     * Test it maps PDF price list file response.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    public function testItMapsPdfPriceListFileResponse()
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
     * Test it maps Excel price list file response.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testItMapsExcelPriceListFileResponse()
    {
        /** @var PriceListResponseDataMapper $parser */
        $parser = $this->app->make(PriceListResponseDataMapper::class);

        $currentPage = 1;

        $quoteFile = (new QuoteFile)->forceFill([
            'id' => (string)Uuid::generate(4),
            'imported_page' => $currentPage,
        ]);

        $mappedRows = $parser->mapDistributorResponse($quoteFile, static::$distrResponse3);

        $this->assertIsArray($mappedRows);
        $this->assertCount(5, $mappedRows);

        $this->assertIsArray($mappedRows[0]);
        $this->assertArrayHasKey('columns_data', $mappedRows[0]);

        $headersFromRow = array_column(json_decode($mappedRows[0]['columns_data'], true), 'header');

        $expectedHeaders = [
            "Support Account I.D. / Custpack",
            "Group Description",
            "Service Agreement ID",
            "System Handle",
            "Document ID",
            "Document Type",
            "Coverage Start",
            "Coverage End",
            "Total Net Price",
            "PO Number",
            "Equipment Number",
            "Product Number",
            "Product Description",
            "Quantity",
            "Support Package",
            "Support Package Description",
            "Service Product Number",
            "Service Product Description",
            "Service Level",
            "Warranty End Date",
            "Support Life End Date",
            "Serial Number",
            "Product Type",
            "Product Line Code/Description",
            "Hardware System Contact",
            "Hardware System Contact Phone Number",
            "Software System Contact",
            "Software System Contact Phone Number",
            "System Manager",
            "System Manager Phone Number",
            "Equipment Location Company",
            "Equipment Location Address 2",
            "Equipment Location Address 3",
            "Equipment Location Address 4",
            "Equipment Location Address 5",
            "Billing Cycle Code",
            "Line Item Total Net Price",
            "Line Item Total List Price",
            "Line Item Monthly List Price",
            "Line Item Monthly Discount",
            "Line Item Monthly Net Price",
            "Line Item Support Start Date",
            "Line Item Support End Date",
            "Environment ID",
            "Sales Organisation",
            "PSP ID",
            "PSP Name",
            "Reseller ID",
            "Reseller Company Name",
            "Reseller Address",
            "Reseller City",
            "Reseller Postal Code",
            "Reseller Region",
            "Reseller District",
        ];

        foreach ($expectedHeaders as $expectedHeader) {
            $this->assertContains($expectedHeader, $headersFromRow);
        }
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

    protected static $distrResponse3 = [
        [
            "header" => [
                "792b7ce2c1e649329e0b924c22e58558" => "Support Account I.D. / Custpack",
                "979e500d089b44b4bdd74a4d4402b983" => "Group Description",
                "5c56624070944e54a02d63ac73edd27a" => "Service Agreement ID",
                "4cb9451b4d0841e0b7b2d9f47b09db2d" => "System Handle",
                "fb491c74c8b249efb19920e85b7b4bcb" => "Document ID",
                "25fea3d77ab443a48f43af92158464fb" => "Document Type",
                "372b427ba2594b1a96e7f58d4ebf1f03" => "Coverage Start",
                "79a07d6176ab4d8492540fe9cec0e3dd" => "Coverage End",
                "6f8ee10fc2874e8ea15ea290d1dd8c2b" => "Total Net Price",
                "948ca0fa80244d45ba07e556e17136e5" => "PO Number",
                "fcde6d9426a54663913c35ef8b07b5c4" => "Equipment Number",
                "e414d5351ddc4bdba42a9e52480e29a0" => "Product Number",
                "d2a59470643a49cfa729bc7bd3dbae9e" => "Product Description",
                "6f5737bd455f4eebb472a56c10b559e4" => "Quantity",
                "52bee543dbc5423dbdaff6190b22c562" => "Support Package",
                "7c120a7f05234bdabd47bb32fe1debc3" => "Support Package Description",
                "8e58f318c39949c49e850a41ca635420" => "Service Product Number",
                "ec8eb1d8b321448887e024b17e69d3e6" => "Service Product Description",
                "9c782e470df542e4881fd085995bb649" => "Service Level",
                "bb0b51b7d6a242c5b0ada7a24b83a511" => "Warranty End Date",
                "a61904dc44604d69b0a041b259418c22" => "Support Life End Date",
                "d8826e9873d346ef99dfa39b69e76845" => "Serial Number",
                "3cd3edd2a5c8446eb6c86661147da803" => "Product Type",
                "a4e9e15e87f44234b00b63495d8ee4b5" => "Product Line Code/Description",
                "2f7479b0be754d52ae59f7937d82ff95" => "Hardware System Contact",
                "28453e2ef23c4fbfb6dddb26721143df" => "Hardware System Contact Phone Number",
                "0d4562e43cb348ec85cfece5a48053cb" => "Software System Contact",
                "edeb6a94e56846f29a87020516048725" => "Software System Contact Phone Number",
                "ede093faf7304f79b5d63e31bb947167" => "System Manager",
                "b306be8a5a3042c9a18d235e17df0f8d" => "System Manager Phone Number",
                "8c5d74e5501f488693df64eace163c78" => "Equipment Location Company",
                "fd3a06bca8ec419bb8e3d2ca3b84b6c0" => "Equipment Location Address 2",
                "79387666ceca4e5582ca7c550e25fe17" => "Equipment Location Address 3",
                "56161018218344bfbb2bcb5f11b68dda" => "Equipment Location Address 4",
                "e9dfa9ac613f43c5b0c05764196a8010" => "Equipment Location Address 5",
                "e19c9f020ac84cc39880e03d86a9bb88" => "Billing Cycle Code",
                "6d0f87f4a9974caf981ad4526530e093" => "Line Item Total Net Price",
                "7eb55b0694504e3196cfb338fd442f86" => "Line Item Total List Price",
                "beeeeeb0df5b412f8e5f80b1ec8ae0d0" => "Line Item Monthly List Price",
                "37d8c922aac743eea9591617946c6eaa" => "Line Item Monthly Discount",
                "03ac5edecacd4f19ae81dcf84ad554d4" => "Line Item Monthly Net Price",
                "bc50927d80aa4d788ef07b18e1b4907f" => "Line Item Support Start Date",
                "a5fdebc4826d46ea88ba73bc86baf901" => "Line Item Support End Date",
                "17bba8652f1b449183301a750c02a164" => "Environment ID",
                "6261d8f681024861a2c619542be87838" => "Sales Organisation",
                "2e30962357e8415f8839ef9e515ff329" => "PSP ID",
                "9d38f1bfb1754953bfd743fa6c8c6e49" => "PSP Name",
                "43d2406d21b04f199d02b5f52882e453" => "Reseller ID",
                "b173d74afd0e48b6a7b6df9418a780ee" => "Reseller Company Name",
                "7003df2278a54cdc97cb54e742759aa0" => "Reseller Address",
                "a5c2d4c3ae7c41ee8ecfff9d0f269833" => "Reseller City",
                "ad56b6f550934185bc254c68720552ce" => "Reseller Postal Code",
                "f911099007f1498395b09db1f70e2064" => "Reseller Region",
                "425d3637a3dd436a89e03d67d0e65dfc" => "Reseller District",
            ],
            "rows" => [
                [
                    "792b7ce2c1e649329e0b924c22e58558" => "SW-CARDIFF",
                    "979e500d089b44b4bdd74a4d4402b983" => "Cardiff County Council",
                    "5c56624070944e54a02d63ac73edd27a" => "1086 9523 1397",
                    "4cb9451b4d0841e0b7b2d9f47b09db2d" => "CARDIFF-09",
                    "fb491c74c8b249efb19920e85b7b4bcb" => 57413074,
                    "25fea3d77ab443a48f43af92158464fb" => "ZQ",
                    "372b427ba2594b1a96e7f58d4ebf1f03" => 44465,
                    "79a07d6176ab4d8492540fe9cec0e3dd" => 44645,
                    "6f8ee10fc2874e8ea15ea290d1dd8c2b" => 1018.44,
                    "948ca0fa80244d45ba07e556e17136e5" => "",
                    "fcde6d9426a54663913c35ef8b07b5c4" => "",
                    "e414d5351ddc4bdba42a9e52480e29a0" => "",
                    "d2a59470643a49cfa729bc7bd3dbae9e" => "",
                    "6f5737bd455f4eebb472a56c10b559e4" => 1,
                    "52bee543dbc5423dbdaff6190b22c562" => "HU4A3AC",
                    "7c120a7f05234bdabd47bb32fe1debc3" => "HPE Tech Care Critical SVC",
                    "8e58f318c39949c49e850a41ca635420" => "HU4A2AC",
                    "ec8eb1d8b321448887e024b17e69d3e6" => "HPE Hardware Tech Support",
                    "9c782e470df542e4881fd085995bb649" => "Onsite Support; Replacement Parts; Critical",
                    "bb0b51b7d6a242c5b0ada7a24b83a511" => "",
                    "a61904dc44604d69b0a041b259418c22" => "",
                    "d8826e9873d346ef99dfa39b69e76845" => "",
                    "3cd3edd2a5c8446eb6c86661147da803" => "Hardware Support Service",
                    "a4e9e15e87f44234b00b63495d8ee4b5" => "72 - MCS Server Support",
                    "2f7479b0be754d52ae59f7937d82ff95" => "Alex Evans",
                    "28453e2ef23c4fbfb6dddb26721143df" => "029 2087 2087",
                    "0d4562e43cb348ec85cfece5a48053cb" => "Alex Evans",
                    "edeb6a94e56846f29a87020516048725" => "029 2087 2087",
                    "ede093faf7304f79b5d63e31bb947167" => "Alex Evans",
                    "b306be8a5a3042c9a18d235e17df0f8d" => "029 2087 2087",
                    "8c5d74e5501f488693df64eace163c78" => "Cardiff County Council",
                    "fd3a06bca8ec419bb8e3d2ca3b84b6c0" => "County Hall",
                    "79387666ceca4e5582ca7c550e25fe17" => "atlantic Wharf,Cardiff",
                    "56161018218344bfbb2bcb5f11b68dda" => "",
                    "e9dfa9ac613f43c5b0c05764196a8010" => "CF10 4UW",
                    "e19c9f020ac84cc39880e03d86a9bb88" => "ZE",
                    "6d0f87f4a9974caf981ad4526530e093" => 0,
                    "7eb55b0694504e3196cfb338fd442f86" => 0,
                    "beeeeeb0df5b412f8e5f80b1ec8ae0d0" => 0,
                    "37d8c922aac743eea9591617946c6eaa" => 0,
                    "03ac5edecacd4f19ae81dcf84ad554d4" => 0,
                    "bc50927d80aa4d788ef07b18e1b4907f" => 44465,
                    "a5fdebc4826d46ea88ba73bc86baf901" => 44645,
                    "17bba8652f1b449183301a750c02a164" => "",
                    "6261d8f681024861a2c619542be87838" => "GB00",
                    "2e30962357e8415f8839ef9e515ff329" => "",
                    "9d38f1bfb1754953bfd743fa6c8c6e49" => "",
                    "43d2406d21b04f199d02b5f52882e453" => "",
                    "b173d74afd0e48b6a7b6df9418a780ee" => "",
                    "7003df2278a54cdc97cb54e742759aa0" => "",
                    "a5c2d4c3ae7c41ee8ecfff9d0f269833" => "",
                    "ad56b6f550934185bc254c68720552ce" => "",
                    "f911099007f1498395b09db1f70e2064" => "",
                    "425d3637a3dd436a89e03d67d0e65dfc" => "",
                ],
                [
                    "792b7ce2c1e649329e0b924c22e58558" => "SW-CARDIFF",
                    "979e500d089b44b4bdd74a4d4402b983" => "Cardiff County Council",
                    "5c56624070944e54a02d63ac73edd27a" => "1086 9523 1397",
                    "4cb9451b4d0841e0b7b2d9f47b09db2d" => "CARDIFF-09",
                    "fb491c74c8b249efb19920e85b7b4bcb" => 57413074,
                    "25fea3d77ab443a48f43af92158464fb" => "ZQ",
                    "372b427ba2594b1a96e7f58d4ebf1f03" => 44465,
                    "79a07d6176ab4d8492540fe9cec0e3dd" => 44645,
                    "6f8ee10fc2874e8ea15ea290d1dd8c2b" => 1018.44,
                    "948ca0fa80244d45ba07e556e17136e5" => "",
                    "fcde6d9426a54663913c35ef8b07b5c4" => 793385280,
                    "e414d5351ddc4bdba42a9e52480e29a0" => "826684-B21",
                    "d2a59470643a49cfa729bc7bd3dbae9e" => "HPE DL380 Gen9 E5-2650v4 2P 32G Perf Svr",
                    "6f5737bd455f4eebb472a56c10b559e4" => 1,
                    "52bee543dbc5423dbdaff6190b22c562" => "HU4A3AC",
                    "7c120a7f05234bdabd47bb32fe1debc3" => "HPE Tech Care Critical SVC",
                    "8e58f318c39949c49e850a41ca635420" => "HU4A2AC",
                    "ec8eb1d8b321448887e024b17e69d3e6" => "HPE Hardware Tech Support",
                    "9c782e470df542e4881fd085995bb649" => "Onsite Support; Replacement Parts; Critical",
                    "bb0b51b7d6a242c5b0ada7a24b83a511" => 43708,
                    "a61904dc44604d69b0a041b259418c22" => "",
                    "d8826e9873d346ef99dfa39b69e76845" => "CZJ6150K6H",
                    "3cd3edd2a5c8446eb6c86661147da803" => "Hardware",
                    "a4e9e15e87f44234b00b63495d8ee4b5" => "96 - Industry Standard Se",
                    "2f7479b0be754d52ae59f7937d82ff95" => "Alex Evans",
                    "28453e2ef23c4fbfb6dddb26721143df" => "029 2087 2087",
                    "0d4562e43cb348ec85cfece5a48053cb" => "Alex Evans",
                    "edeb6a94e56846f29a87020516048725" => "029 2087 2087",
                    "ede093faf7304f79b5d63e31bb947167" => "Alex Evans",
                    "b306be8a5a3042c9a18d235e17df0f8d" => "029 2087 2087",
                    "8c5d74e5501f488693df64eace163c78" => "Cardiff County Council",
                    "fd3a06bca8ec419bb8e3d2ca3b84b6c0" => "County Hall",
                    "79387666ceca4e5582ca7c550e25fe17" => "atlantic Wharf,Cardiff",
                    "56161018218344bfbb2bcb5f11b68dda" => "",
                    "e9dfa9ac613f43c5b0c05764196a8010" => "CF10 4UW",
                    "e19c9f020ac84cc39880e03d86a9bb88" => "ZE",
                    "6d0f87f4a9974caf981ad4526530e093" => 861,
                    "7eb55b0694504e3196cfb338fd442f86" => 1050,
                    "beeeeeb0df5b412f8e5f80b1ec8ae0d0" => 175,
                    "37d8c922aac743eea9591617946c6eaa" => -31.5,
                    "03ac5edecacd4f19ae81dcf84ad554d4" => 143.5,
                    "bc50927d80aa4d788ef07b18e1b4907f" => 44465,
                    "a5fdebc4826d46ea88ba73bc86baf901" => 44645,
                    "17bba8652f1b449183301a750c02a164" => "",
                    "6261d8f681024861a2c619542be87838" => "GB00",
                    "2e30962357e8415f8839ef9e515ff329" => "",
                    "9d38f1bfb1754953bfd743fa6c8c6e49" => "",
                    "43d2406d21b04f199d02b5f52882e453" => "",
                    "b173d74afd0e48b6a7b6df9418a780ee" => "",
                    "7003df2278a54cdc97cb54e742759aa0" => "",
                    "a5c2d4c3ae7c41ee8ecfff9d0f269833" => "",
                    "ad56b6f550934185bc254c68720552ce" => "",
                    "f911099007f1498395b09db1f70e2064" => "",
                    "425d3637a3dd436a89e03d67d0e65dfc" => "",
                ],
                [
                    "792b7ce2c1e649329e0b924c22e58558" => "SW-CARDIFF",
                    "979e500d089b44b4bdd74a4d4402b983" => "Cardiff County Council",
                    "5c56624070944e54a02d63ac73edd27a" => "1086 9523 1397",
                    "4cb9451b4d0841e0b7b2d9f47b09db2d" => "CARDIFF-09",
                    "fb491c74c8b249efb19920e85b7b4bcb" => 57413074,
                    "25fea3d77ab443a48f43af92158464fb" => "ZQ",
                    "372b427ba2594b1a96e7f58d4ebf1f03" => 44465,
                    "79a07d6176ab4d8492540fe9cec0e3dd" => 44645,
                    "6f8ee10fc2874e8ea15ea290d1dd8c2b" => 1018.44,
                    "948ca0fa80244d45ba07e556e17136e5" => "",
                    "fcde6d9426a54663913c35ef8b07b5c4" => "",
                    "e414d5351ddc4bdba42a9e52480e29a0" => "",
                    "d2a59470643a49cfa729bc7bd3dbae9e" => "",
                    "6f5737bd455f4eebb472a56c10b559e4" => 1,
                    "52bee543dbc5423dbdaff6190b22c562" => "HU4A3AC",
                    "7c120a7f05234bdabd47bb32fe1debc3" => "HPE Tech Care Critical SVC",
                    "8e58f318c39949c49e850a41ca635420" => "HU4A1AC",
                    "ec8eb1d8b321448887e024b17e69d3e6" => "HPE Remote Tech Support",
                    "9c782e470df542e4881fd085995bb649" => "Technical Support; General Technical Guidance; Critical",
                    "bb0b51b7d6a242c5b0ada7a24b83a511" => "",
                    "a61904dc44604d69b0a041b259418c22" => "",
                    "d8826e9873d346ef99dfa39b69e76845" => "",
                    "3cd3edd2a5c8446eb6c86661147da803" => "Environmental Service",
                    "a4e9e15e87f44234b00b63495d8ee4b5" => "72 - MCS Server Support",
                    "2f7479b0be754d52ae59f7937d82ff95" => "Alex Evans",
                    "28453e2ef23c4fbfb6dddb26721143df" => "029 2087 2087",
                    "0d4562e43cb348ec85cfece5a48053cb" => "Alex Evans",
                    "edeb6a94e56846f29a87020516048725" => "029 2087 2087",
                    "ede093faf7304f79b5d63e31bb947167" => "Alex Evans",
                    "b306be8a5a3042c9a18d235e17df0f8d" => "029 2087 2087",
                    "8c5d74e5501f488693df64eace163c78" => "Cardiff County Council",
                    "fd3a06bca8ec419bb8e3d2ca3b84b6c0" => "County Hall",
                    "79387666ceca4e5582ca7c550e25fe17" => "atlantic Wharf,Cardiff",
                    "56161018218344bfbb2bcb5f11b68dda" => "",
                    "e9dfa9ac613f43c5b0c05764196a8010" => "CF10 4UW",
                    "e19c9f020ac84cc39880e03d86a9bb88" => "ZE",
                    "6d0f87f4a9974caf981ad4526530e093" => 0,
                    "7eb55b0694504e3196cfb338fd442f86" => 0,
                    "beeeeeb0df5b412f8e5f80b1ec8ae0d0" => 0,
                    "37d8c922aac743eea9591617946c6eaa" => 0,
                    "03ac5edecacd4f19ae81dcf84ad554d4" => 0,
                    "bc50927d80aa4d788ef07b18e1b4907f" => 44465,
                    "a5fdebc4826d46ea88ba73bc86baf901" => 44645,
                    "17bba8652f1b449183301a750c02a164" => "",
                    "6261d8f681024861a2c619542be87838" => "GB00",
                    "2e30962357e8415f8839ef9e515ff329" => "",
                    "9d38f1bfb1754953bfd743fa6c8c6e49" => "",
                    "43d2406d21b04f199d02b5f52882e453" => "",
                    "b173d74afd0e48b6a7b6df9418a780ee" => "",
                    "7003df2278a54cdc97cb54e742759aa0" => "",
                    "a5c2d4c3ae7c41ee8ecfff9d0f269833" => "",
                    "ad56b6f550934185bc254c68720552ce" => "",
                    "f911099007f1498395b09db1f70e2064" => "",
                    "425d3637a3dd436a89e03d67d0e65dfc" => "",
                ],
                [
                    "792b7ce2c1e649329e0b924c22e58558" => "SW-CARDIFF",
                    "979e500d089b44b4bdd74a4d4402b983" => "Cardiff County Council",
                    "5c56624070944e54a02d63ac73edd27a" => "1086 9523 1397",
                    "4cb9451b4d0841e0b7b2d9f47b09db2d" => "CARDIFF-09",
                    "fb491c74c8b249efb19920e85b7b4bcb" => 57413074,
                    "25fea3d77ab443a48f43af92158464fb" => "ZQ",
                    "372b427ba2594b1a96e7f58d4ebf1f03" => 44465,
                    "79a07d6176ab4d8492540fe9cec0e3dd" => 44645,
                    "6f8ee10fc2874e8ea15ea290d1dd8c2b" => 1018.44,
                    "948ca0fa80244d45ba07e556e17136e5" => "",
                    "fcde6d9426a54663913c35ef8b07b5c4" => 793385280,
                    "e414d5351ddc4bdba42a9e52480e29a0" => "826684-B21",
                    "d2a59470643a49cfa729bc7bd3dbae9e" => "HPE DL380 Gen9 E5-2650v4 2P 32G Perf Svr",
                    "6f5737bd455f4eebb472a56c10b559e4" => 1,
                    "52bee543dbc5423dbdaff6190b22c562" => "HU4A3AC",
                    "7c120a7f05234bdabd47bb32fe1debc3" => "HPE Tech Care Critical SVC",
                    "8e58f318c39949c49e850a41ca635420" => "HU4A1AC",
                    "ec8eb1d8b321448887e024b17e69d3e6" => "HPE Remote Tech Support",
                    "9c782e470df542e4881fd085995bb649" => "Technical Support; General Technical Guidance; Critical",
                    "bb0b51b7d6a242c5b0ada7a24b83a511" => 43708,
                    "a61904dc44604d69b0a041b259418c22" => "",
                    "d8826e9873d346ef99dfa39b69e76845" => "CZJ6150K6H",
                    "3cd3edd2a5c8446eb6c86661147da803" => "Hardware",
                    "a4e9e15e87f44234b00b63495d8ee4b5" => "96 - Industry Standard Se",
                    "2f7479b0be754d52ae59f7937d82ff95" => "Alex Evans",
                    "28453e2ef23c4fbfb6dddb26721143df" => "029 2087 2087",
                    "0d4562e43cb348ec85cfece5a48053cb" => "Alex Evans",
                    "edeb6a94e56846f29a87020516048725" => "029 2087 2087",
                    "ede093faf7304f79b5d63e31bb947167" => "Alex Evans",
                    "b306be8a5a3042c9a18d235e17df0f8d" => "029 2087 2087",
                    "8c5d74e5501f488693df64eace163c78" => "Cardiff County Council",
                    "fd3a06bca8ec419bb8e3d2ca3b84b6c0" => "County Hall",
                    "79387666ceca4e5582ca7c550e25fe17" => "atlantic Wharf,Cardiff",
                    "56161018218344bfbb2bcb5f11b68dda" => "",
                    "e9dfa9ac613f43c5b0c05764196a8010" => "CF10 4UW",
                    "e19c9f020ac84cc39880e03d86a9bb88" => "ZE",
                    "6d0f87f4a9974caf981ad4526530e093" => 132.84,
                    "7eb55b0694504e3196cfb338fd442f86" => 162,
                    "beeeeeb0df5b412f8e5f80b1ec8ae0d0" => 27,
                    "37d8c922aac743eea9591617946c6eaa" => -4.86,
                    "03ac5edecacd4f19ae81dcf84ad554d4" => 22.14,
                    "bc50927d80aa4d788ef07b18e1b4907f" => 44465,
                    "a5fdebc4826d46ea88ba73bc86baf901" => 44645,
                    "17bba8652f1b449183301a750c02a164" => "",
                    "6261d8f681024861a2c619542be87838" => "GB00",
                    "2e30962357e8415f8839ef9e515ff329" => "",
                    "9d38f1bfb1754953bfd743fa6c8c6e49" => "",
                    "43d2406d21b04f199d02b5f52882e453" => "",
                    "b173d74afd0e48b6a7b6df9418a780ee" => "",
                    "7003df2278a54cdc97cb54e742759aa0" => "",
                    "a5c2d4c3ae7c41ee8ecfff9d0f269833" => "",
                    "ad56b6f550934185bc254c68720552ce" => "",
                    "f911099007f1498395b09db1f70e2064" => "",
                    "425d3637a3dd436a89e03d67d0e65dfc" => "",
                ],
                [
                    "792b7ce2c1e649329e0b924c22e58558" => "SW-CARDIFF",
                    "979e500d089b44b4bdd74a4d4402b983" => "Cardiff County Council",
                    "5c56624070944e54a02d63ac73edd27a" => "1086 9523 1397",
                    "4cb9451b4d0841e0b7b2d9f47b09db2d" => "CARDIFF-09",
                    "fb491c74c8b249efb19920e85b7b4bcb" => 57413074,
                    "25fea3d77ab443a48f43af92158464fb" => "ZQ",
                    "372b427ba2594b1a96e7f58d4ebf1f03" => 44465,
                    "79a07d6176ab4d8492540fe9cec0e3dd" => 44645,
                    "6f8ee10fc2874e8ea15ea290d1dd8c2b" => 1018.44,
                    "948ca0fa80244d45ba07e556e17136e5" => "",
                    "fcde6d9426a54663913c35ef8b07b5c4" => 881411426,
                    "e414d5351ddc4bdba42a9e52480e29a0" => "765762-B21",
                    "d2a59470643a49cfa729bc7bd3dbae9e" => "HP Z iLO Adv 3yr TS-U Lic-BTO (GLiS-PTS)",
                    "6f5737bd455f4eebb472a56c10b559e4" => 1,
                    "52bee543dbc5423dbdaff6190b22c562" => "HU4A3AC",
                    "7c120a7f05234bdabd47bb32fe1debc3" => "HPE Tech Care Critical SVC",
                    "8e58f318c39949c49e850a41ca635420" => "HU4A1AC",
                    "ec8eb1d8b321448887e024b17e69d3e6" => "HPE Remote Tech Support",
                    "9c782e470df542e4881fd085995bb649" => "Technical Support; General Technical Guidance; Critical",
                    "bb0b51b7d6a242c5b0ada7a24b83a511" => "",
                    "a61904dc44604d69b0a041b259418c22" => "",
                    "d8826e9873d346ef99dfa39b69e76845" => "CZJ6150K6H",
                    "3cd3edd2a5c8446eb6c86661147da803" => "Software",
                    "a4e9e15e87f44234b00b63495d8ee4b5" => "K3 - HP Technology Softwa",
                    "2f7479b0be754d52ae59f7937d82ff95" => "Alex Evans",
                    "28453e2ef23c4fbfb6dddb26721143df" => "029 2087 2087",
                    "0d4562e43cb348ec85cfece5a48053cb" => "Alex Evans",
                    "edeb6a94e56846f29a87020516048725" => "029 2087 2087",
                    "ede093faf7304f79b5d63e31bb947167" => "Alex Evans",
                    "b306be8a5a3042c9a18d235e17df0f8d" => "029 2087 2087",
                    "8c5d74e5501f488693df64eace163c78" => "Cardiff County Council",
                    "fd3a06bca8ec419bb8e3d2ca3b84b6c0" => "County Hall",
                    "79387666ceca4e5582ca7c550e25fe17" => "atlantic Wharf,Cardiff",
                    "56161018218344bfbb2bcb5f11b68dda" => "",
                    "e9dfa9ac613f43c5b0c05764196a8010" => "CF10 4UW",
                    "e19c9f020ac84cc39880e03d86a9bb88" => "ZE",
                    "6d0f87f4a9974caf981ad4526530e093" => 24.6,
                    "7eb55b0694504e3196cfb338fd442f86" => 30,
                    "beeeeeb0df5b412f8e5f80b1ec8ae0d0" => 5,
                    "37d8c922aac743eea9591617946c6eaa" => -0.9,
                    "03ac5edecacd4f19ae81dcf84ad554d4" => 4.1,
                    "bc50927d80aa4d788ef07b18e1b4907f" => 44465,
                    "a5fdebc4826d46ea88ba73bc86baf901" => 44645,
                    "17bba8652f1b449183301a750c02a164" => "",
                    "6261d8f681024861a2c619542be87838" => "GB00",
                    "2e30962357e8415f8839ef9e515ff329" => "",
                    "9d38f1bfb1754953bfd743fa6c8c6e49" => "",
                    "43d2406d21b04f199d02b5f52882e453" => "",
                    "b173d74afd0e48b6a7b6df9418a780ee" => "",
                    "7003df2278a54cdc97cb54e742759aa0" => "",
                    "a5c2d4c3ae7c41ee8ecfff9d0f269833" => "",
                    "ad56b6f550934185bc254c68720552ce" => "",
                    "f911099007f1498395b09db1f70e2064" => "",
                    "425d3637a3dd436a89e03d67d0e65dfc" => "",
                ],
            ],
            "sheet_index" => 0,
        ],
    ];
}
