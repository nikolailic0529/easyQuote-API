<?php

namespace Tests\Unit\Parser;

use Tests\TestCase;
use Webpatser\Uuid\Uuid;
use Illuminate\Support\Arr;
use App\Models\QuoteFile\QuoteFile;
use App\Services\DocumentEngine\ParseDistributorPDF;
use App\Services\DocumentEngine\ParsePaymentPDF;
use App\Services\DocumentProcessor\DocumentEngine\PaymentPDF;
use App\Services\DocumentProcessor\DocumentEngine\DistributorPDF;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * @group build
 */
class DocumentEngineTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test Distributor File Processor.
     *
     * @return void
     */
    public function testDistributorFileProcessor()
    {
        $this->markTestSkipped();

        config(['documentparse.default' => 'document_api']);
        config(['services.document_api.url' => 'http://18.134.146.232:1337']);

        $response = (new ParseDistributorPDF)
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
     * Test Payment Schedule File Processor.
     *
     * @return void
     */
    public function testScheduleFileProcessor()
    {
        $this->markTestSkipped();

        config(['documentparse.default' => 'document_api']);
        config(['services.document_api.url' => 'http://18.134.146.232:1337']);

        $response = (new ParsePaymentPDF($this->app->make(LoggerInterface::class)))
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
     */
    public function testDistributorResponseMapping()
    {
        /** @var DistributorPDF */
        $parser = $this->app->make(DistributorPDF::class);

        $class = new ReflectionClass($parser);
        $method = $class->getMethod('mapDistributorResponse');
        $method->setAccessible(true);

        $currentPage = 1;

        $quoteFile = (new QuoteFile)->forceFill([
            'id' => (string) Uuid::generate(4),
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

            $rows = array_map(fn ($row) => $row + [
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
    }

    /**
     * Test Payment Schedule Response mapping.
     *
     * @return void
     */
    public function testPaymentResponseMapping()
    {
        /** @var PaymentPDF */
        $parser = $this->app->make(PaymentPDF::class);

        $class = new ReflectionClass($parser);
        $method = $class->getMethod('mapPaymentResponse');
        $method->setAccessible(true);

        $currentPage = 1;

        $quoteFile = (new QuoteFile)->forceFill([
            'id' => (string) Uuid::generate(4),
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
            ]

        ], $mappedResponse);
    }

    public function testUnprocessablePaymentResponseMapping()
    {
        /** @var PaymentPDF */
        $parser = $this->app->make(PaymentPDF::class);

        $class = new ReflectionClass($parser);
        $method = $class->getMethod('mapPaymentResponse');
        $method->setAccessible(true);

        $currentPage = 1;

        $quoteFile = (new QuoteFile)->forceFill([
            'id' => (string) Uuid::generate(4),
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
            ]
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
            ]
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
            ]
        ]
    ];

    protected static $distrResponse = [
        [
            "attributes" => [
                "pricing_document" => "56784797",
                "system_handle" => "SUPPINBA-UK KINGDOM",
                "service_agreement_id" => "1086 5193 2250",
            ],
            "header" => null,
            "rows" => null
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
                "price_gbp" => "Price/GBP"
            ],
            "rows" => [
                [
                    "product_no" => "H7J33AC",
                    "description" => "HPE Foundation Care NBD wDMR SVC",
                    "serial_no" => "",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => ""
                ],
                [
                    "product_no" => "661189-B21",
                    "description" => "HP DL360e Gen8 8SFF CTO Server",
                    "serial_no" => "CZ3323FBRL",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "25.89"
                ],
                [
                    "product_no" => "661189-B21",
                    "description" => "HP DL360e Gen8 8SFF CTO Server",
                    "serial_no" => "CZ3323FBRL",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "4.89"
                ],
                [
                    "product_no" => "UJ558AC",
                    "description" => "HPE Ind Std Svrs Return to HW Supp",
                    "serial_no" => "",
                    "from" => "",
                    "coverage_period_to" => "19.06.2019",
                    "qty" => "",
                    "price_gbp" => "1,290.00",
                ]
            ]
        ],
        [
            "attributes" => [
                "pricing_document" => "56784797",
                "system_handle" => "SUPPINBA-UK KINGDOM",
                "service_agreement_id" => "1086 5193 2250",
            ],
            "header" => null,
            "rows" => null
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
                "price_gbp" => "Price/GBP"
            ],
            "rows" => [
                [
                    "product_no" => "H7J33AC",
                    "description" => "HPE Foundation Care NBD wDMR SVC",
                    "serial_no" => "",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => ""
                ],
                [
                    "product_no" => "661189-B21",
                    "description" => "HP DL360e Gen8 8SFF CTO Server",
                    "serial_no" => "CZJ302051C",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "33.17"
                ],
                [
                    "product_no" => "661189-B21",
                    "description" => "HP DL360e Gen8 8SFF CTO Server",
                    "serial_no" => "CZJ302051C",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "5.87"
                ]
            ]
        ],
        [
            "attributes" => [
                "pricing_document" => "56784797",
                "system_handle" => "SUPPINBA-UK KINGDOM",
                "service_agreement_id" => "1086 5193 2250",
            ],
            "header" => null,
            "rows" => null
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
                "price_gbp" => "Price/GBP"
            ],
            "rows" => [
                [
                    "product_no" => "H7J33AC",
                    "description" => "HPE Foundation Care NBD wDMR SVC",
                    "serial_no" => "",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => ""
                ],
                [
                    "product_no" => "661189-B21",
                    "description" => "HP DL360e Gen8 8SFF CTO Server",
                    "serial_no" => "CZJ3020539",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "42.14"
                ],
                [
                    "product_no" => "661189-B21",
                    "description" => "HP DL360e Gen8 8SFF CTO Server",
                    "serial_no" => "CZJ3020539",
                    "from" => "",
                    "coverage_period_to" => "",
                    "qty" => "",
                    "price_gbp" => "6.02"
                ]
            ]
        ]
    ];
}
