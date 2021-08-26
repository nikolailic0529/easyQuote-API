<?php

namespace Tests\Unit\Parser;

use App\Services\DocumentReaders\Models\Row;
use App\Contracts\Services\{ManagesDocumentProcessors, PdfParserInterface, WordParserInterface};
use App\Models\{QuoteFile\ImportedRow, QuoteFile\QuoteFileFormat};
use App\Models\QuoteFile\QuoteFile;
use App\Services\DocumentProcessor\EasyQuote\EqExcelPriceListProcessor;
use App\Services\DocumentReaders\ExcelPriceListReader;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\{Arr, Collection, Facades\File, Facades\Storage, Str};
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * @group build
 */
class DistributorPriceListParsingTest extends TestCase
{
    use DatabaseTransactions;

    protected static function storeText(string $filePath, array $raw)
    {
        $fileName = Str::slug(File::name($filePath), '_');

        $dir = "tests/Unit/Data/distributor-files-test/$fileName";

        if (!is_dir($dir)) {
            mkdir($dir);
        }

        foreach ($raw as $text) {
            $page = $text['page'];

            $pagePath = base_path("$dir/{$fileName}_{$page}.txt");

            file_put_contents($pagePath, $text['content']);
        }
    }

    /**
     * @group parsing-price-list-xlsx
     */
    public function test_parses_td_9735_gt_movie_via_europlus_direct_renewal_sh4w3h_xlxs()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/TD-9735, GT Motive via EuroplusDirect, renewal SH4W3H.xlsx');

        $storage = Storage::fake();

        $storage->put($fileName = Str::random(40).'.xlsx', file_get_contents($filePath));

        /** @var QuoteFile $quoteFile */
        $quoteFile = factory(QuoteFile::class)->create([
            'original_file_path' => $fileName,
            'original_file_name' => 'TD-9735, GT Motive via EuroplusDirect, renewal SH4W3H.xlsx',
            'file_type' => 'Distributor Price List',
            'pages' => 2,
            'quote_file_format_id' => QuoteFileFormat::value('id'),
            'imported_page' => 2
        ]);

        $excelProcessor = $this->app[EqExcelPriceListProcessor::class];

        $excelProcessor->process($quoteFile);

//        $quoteFile->rowsData->map(function (ImportedRow $importedRow) {
//          return $importedRow->columns_data->pluck('value', 'header');
//        })->dd();

        $this->assertCount(27, $quoteFile->rowsData);
    }

    /**
     * @group parsing-price-list-xlsx
     */
    public function test_parses_europlusdirect_ltd_1044696_28_04_2021_xlsx()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/EUROPLUSDIRECT LTD_1044696_28-04-2021.xlsx');

        $storage = Storage::fake();

        $storage->put($fileName = Str::random(40).'.xlsx', file_get_contents($filePath));

        /** @var QuoteFile $quoteFile */
        $quoteFile = factory(QuoteFile::class)->create([
            'original_file_path' => $fileName,
            'original_file_name' => 'EUROPLUSDIRECT LTD_1044696_28-04-2021.xlsx',
            'file_type' => 'Distributor Price List',
            'pages' => 2,
            'quote_file_format_id' => QuoteFileFormat::value('id'),
            'imported_page' => 2
        ]);

        $excelProcessor = $this->app[EqExcelPriceListProcessor::class];

        $excelProcessor->process($quoteFile);

        $this->assertCount(4, $quoteFile->rowsData);
    }

    /**
     * @group parsing-price-list-xlsx
     */
    public function test_parses_unicredit_lenovo_tesedi_quote_tier1_zkh0d4_new_xlsx()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/Unicredit Lenovo Tesedi Quote Tier 1 ZKHOD4 NEW.xlsx');

        $storage = Storage::fake();

        $storage->put($fileName = Str::random(40).'.xlsx', file_get_contents($filePath));

        /** @var QuoteFile $quoteFile */
        $quoteFile = factory(QuoteFile::class)->create([
            'original_file_path' => $fileName,
            'original_file_name' => 'Unicredit Lenovo Tesedi Quote Tier 1 ZKHOD4 NEW.xlsx',
            'file_type' => 'Distributor Price List',
            'pages' => 2,
            'quote_file_format_id' => QuoteFileFormat::value('id'),
            'imported_page' => 2
        ]);

        $excelProcessor = $this->app[EqExcelPriceListProcessor::class];

        $excelProcessor->process($quoteFile);

        $valuesOfRows = $quoteFile->rowsData->pluck('columns_data')->map(function (Collection $values) {
            return $values->mapWithKeys(function (object $value) {
                return [$value->header => $value->value];
            })->all();
        })->all();

        $this->assertCount(4, $valuesOfRows);

        $expectedRows = [
            [
                "Machine Type" => "7X06",
                "Charges Start" => "16/05/2021",
                "Description" => "ThinkSystem SR650 - 3yr Warranty",
                "Nbr" => 4,
                "Services" => "Warranty Service Upgrade",
                "Charges Stop" => "15/05/2024",
                "Sla" => "Tech Install, SBD 24x7",
                "Installation Customer Number" => 1310182734,
                "Quantity" => 1,
                "Mod/Feat" => "CTO1WW",
                "Order/Serial" => "S4CMM564",
                "BP CHARGES BILLING PERIOD 2021-05-16 - 2024-05-15" => 1264.53,
            ],
            [
                "Machine Type" => "7X06",
                "Charges Start" => "16/05/2021",
                "Description" => "ThinkSystem SR650 - 3yr Warranty",
                "Nbr" => 1,
                "Services" => "Warranty Service Upgrade",
                "Charges Stop" => "15/05/2024",
                "Sla" => "Tech Install, SBD 24x7",
                "Installation Customer Number" => 1310182734,
                "Quantity" => 1,
                "Mod/Feat" => "CTO1WW",
                "Order/Serial" => "S4CMM561",
                "BP CHARGES BILLING PERIOD 2021-05-16 - 2024-05-15" => 1264.53,
            ],
            [
                "Machine Type" => "7X06",
                "Charges Start" => "16/05/2021",
                "Description" => "ThinkSystem SR650 - 3yr Warranty",
                "Nbr" => 2,
                "Services" => "Warranty Service Upgrade",
                "Charges Stop" => "15/05/2024",
                "Sla" => "Tech Install, SBD 24x7",
                "Installation Customer Number" => 1310182734,
                "Quantity" => 1,
                "Mod/Feat" => "CTO1WW",
                "Order/Serial" => "S4CMM562",
                "BP CHARGES BILLING PERIOD 2021-05-16 - 2024-05-15" => 1264.53,
            ],
            [
                "Machine Type" => "7X06",
                "Charges Start" => "16/05/2021",
                "Description" => "ThinkSystem SR650 - 3yr Warranty",
                "Nbr" => 3,
                "Services" => "Warranty Service Upgrade",
                "Charges Stop" => "15/05/2024",
                "Sla" => "Tech Install, SBD 24x7",
                "Installation Customer Number" => 1310182734,
                "Quantity" => 1,
                "Mod/Feat" => "CTO1WW",
                "Order/Serial" => "S4CMM563",
                "BP CHARGES BILLING PERIOD 2021-05-16 - 2024-05-15" => 1264.53,
            ]
        ];

        foreach ($expectedRows as $row) {
            $this->assertContainsEquals($row, $valuesOfRows);
        }


    }

    /**
     * @group parsing-price-list-pdf
     */
    public function test_parses_spw_bou_pdf()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/SPW-BOU.pdf');

        $content = $this->pdfParser()->getText($filePath);

        $result = $this->pdfParser()->parse($content)['pages'];

        $this->assertEmpty($result[0]['rows']);

        $this->assertCount(11, $result[1]['rows']);

        $this->assertContainsEquals([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "1H210-083L4-F8U42-A22RP-3MLL5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "1H210-083L4-F8U42-A22RP-3MLL5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "5N28K-48LKM-Y8K43-ALC0K-C19L1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "5N28K-48LKM-Y8K43-ALC0K-C19L1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "5J690-08JLM-A8K4A-A222H-3E1J1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "5J690-08JLM-A8K4A-A222H-3E1J1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "5N600-483Q6-A8K4C-ALA0H-2RV31",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "5N600-483Q6-A8K4C-ALA0H-2RV31",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "5J29H-081QP-Y8K4C-AHCHH-2NC35",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "5J29H-081QP-Y8K4C-AHCHH-2NC35",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "H7J34AC",
            "description" => "HPE Foundation Care 24x7 SVC",
            "serial_no" => null,
            "date_from" => null,
            "date_to" => null,
            "qty" => null,
            "price" => null,
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[1]['rows']);

        $this->assertCount(18, $result[2]['rows']);

        $this->assertContainsEquals([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "5N210-081VM-U8V42-A1AAP-20VJ5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[2]['rows']);
        $this->assertContainsEquals([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "5N210-081VM-U8V42-A1AAP-20VJ5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[2]['rows']);
        $this->assertContainsEquals([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "1H61K-0X2P4-F8K3C-A8986-34C45",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[2]['rows']);
        $this->assertContainsEquals([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "1H61K-0X2P4-F8K3C-A8986-34C45",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[2]['rows']);
        $this->assertContainsEquals([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "1H210-083L4-F8U42-A22RP-3MLL5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[2]['rows']);
        $this->assertContainsEquals([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "1H210-083L4-F8U42-A22RP-3MLL5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[2]['rows']);
        $this->assertContainsEquals([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "5N28K-48LKM-Y8K43-ALC0K-C19L1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[2]['rows']);
        $this->assertContainsEquals([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "5N28K-48LKM-Y8K43-ALC0K-C19L1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[2]['rows']);
        $this->assertContainsEquals([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "5J690-08JLM-A8K4A-A222H-3E1J1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[2]['rows']);
        $this->assertContainsEquals([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "5J690-08JLM-A8K4A-A222H-3E1J1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[2]['rows']);
        $this->assertContainsEquals([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "5N600-483Q6-A8K4C-ALA0H-2RV31",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[2]['rows']);
        $this->assertContainsEquals([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "5N600-483Q6-A8K4C-ALA0H-2RV31",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[2]['rows']);
        $this->assertContainsEquals([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "5J29H-081QP-Y8K4C-AHCHH-2NC35",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[2]['rows']);
        $this->assertContainsEquals([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "5J29H-081QP-Y8K4C-AHCHH-2NC35",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[2]['rows']);
        $this->assertContainsEquals([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "5N210-081VM-U8V42-A1AAP-20VJ5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[2]['rows']);
        $this->assertContainsEquals([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "5N210-081VM-U8V42-A1AAP-20VJ5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[2]['rows']);
        $this->assertContainsEquals([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "1H61K-0X2P4-F8K3C-A8986-34C45",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[2]['rows']);
        $this->assertContainsEquals([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "1H61K-0X2P4-F8K3C-A8986-34C45",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0276",
            "_one_pay" => false,
        ], $result[2]['rows']);

        $this->assertEmpty($result[3]['rows']);

        $this->assertCount(7, $result[4]['rows']);

        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "5N600-481U6-F8K4A-AT3HK-AH911",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0402",
            "_one_pay" => false,
        ], $result[4]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "5N600-481U6-F8K4A-AT3HK-AH911",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0402",
            "_one_pay" => false,
        ], $result[4]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "1N21K-083L4-U8V4A-AV30M-3M3J1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0402",
            "_one_pay" => false,
        ], $result[4]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "1N21K-083L4-U8V4A-AV30M-3M3J1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0402",
            "_one_pay" => false,
        ], $result[4]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "HJ20K-4X0PP-U8U32-A29A0-3J9M1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0402",
            "_one_pay" => false,
        ], $result[4]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "HJ20K-4X0PP-U8U32-A29A0-3J9M1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0402",
            "_one_pay" => false,
        ], $result[4]['rows']);
        $this->assertContains([
            "product_no" => "H7J34AC",
            "description" => "HPE Foundation Care 24x7 SVC",
            "serial_no" => null,
            "date_from" => null,
            "date_to" => null,
            "qty" => null,
            "price" => null,
            "searchable" => "1086 6360 0402",
            "_one_pay" => false,
        ], $result[4]['rows']);

        $this->assertCount(6, $result[5]['rows']);

        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "5N600-481U6-F8K4A-AT3HK-AH911",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0402",
            "_one_pay" => false,
        ], $result[5]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "5N600-481U6-F8K4A-AT3HK-AH911",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0402",
            "_one_pay" => false,
        ], $result[5]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "1N21K-083L4-U8V4A-AV30M-3M3J1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0402",
            "_one_pay" => false,
        ], $result[5]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "1N21K-083L4-U8V4A-AV30M-3M3J1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0402",
            "_one_pay" => false,
        ], $result[5]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "HJ20K-4X0PP-U8U32-A29A0-3J9M1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0402",
            "_one_pay" => false,
        ], $result[5]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "HJ20K-4X0PP-U8U32-A29A0-3J9M1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0402",
            "_one_pay" => false,
        ], $result[5]['rows']);

        $this->assertCount(11, $result[6]['rows']);

        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "5N20K-483KP-Y8V4C-AAAUK-2H915",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[6]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "5N20K-483KP-Y8V4C-AAAUK-2H915",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[6]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "5N212-48LK6-Y8L4A-A8AAP-AX115",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[6]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "5N212-48LK6-Y8L4A-A8AAP-AX115",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[6]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "M029K-483L4-U8L4A-AKCKH-39J11",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[6]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "M029K-483L4-U8L4A-AKCKH-39J11",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[6]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "M4602-48JYP-F8U43-AHA2K-3DV35",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[6]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "M4602-48JYP-F8U43-AHA2K-3DV35",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[6]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "H069H-083P4-F8K43-AL30H-2J315",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[6]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "H069H-083P4-F8K43-AL30H-2J315",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[6]['rows']);
        $this->assertContains([
            "product_no" => "H7J34AC",
            "description" => "HPE Foundation Care 24x7 SVC",
            "serial_no" => null,
            "date_from" => null,
            "date_to" => null,
            "qty" => null,
            "price" => null,
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[6]['rows']);

        $this->assertCount(21, $result[7]['rows']);

        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "H060H-08JVM-Y8K4A-A2A2P-3X1L1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "H060H-08JVM-Y8K4A-A2A2P-3X1L1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "M461H-48LUM-F8V4A-AKCUH-A0T15",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "M461H-48LUM-F8V4A-AKCUH-A0T15",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "M460K-481U6-Y8L43-A1CRH-AECJ5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "M460K-481U6-Y8L43-A1CRH-AECJ5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "H0680-08JYP-Y8U43-ALCAH-CR315",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "H0680-08JYP-Y8U43-ALCAH-CR315",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "M429K-483LP-U8K43-AL38M-CM115",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "M429K-483LP-U8K43-AL38M-CM115",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "JH282-0X0PP-Y8V33-AR1R0-C1VM5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "JH282-0X0PP-Y8V33-AR1R0-C1VM5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "5N20K-483KP-Y8V4C-AAAUK-2H915",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "5N20K-483KP-Y8V4C-AAAUK-2H915",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "5N212-48LK6-Y8L4A-A8AAP-AX115",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "5N212-48LK6-Y8L4A-A8AAP-AX115",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "M029K-483L4-U8L4A-AKCKH-39J11",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "M029K-483L4-U8L4A-AKCKH-39J11",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "M4602-48JYP-F8U43-AHA2K-3DV35",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "M4602-48JYP-F8U43-AHA2K-3DV35",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "H069H-083P4-F8K43-AL30H-2J315",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[7]['rows']);

        $this->assertCount(13, $result[8]['rows']);

        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "H069H-083P4-F8K43-AL30H-2J315",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[8]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "H060H-08JVM-Y8K4A-A2A2P-3X1L1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[8]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "H060H-08JVM-Y8K4A-A2A2P-3X1L1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[8]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "M461H-48LUM-F8V4A-AKCUH-A0T15",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[8]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "M461H-48LUM-F8V4A-AKCUH-A0T15",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[8]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "M460K-481U6-Y8L43-A1CRH-AECJ5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[8]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "M460K-481U6-Y8L43-A1CRH-AECJ5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[8]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "H0680-08JYP-Y8U43-ALCAH-CR315",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[8]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "H0680-08JYP-Y8U43-ALCAH-CR315",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[8]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "M429K-483LP-U8K43-AL38M-CM115",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[8]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "M429K-483LP-U8K43-AL38M-CM115",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[8]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "JH282-0X0PP-Y8V33-AR1R0-C1VM5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[8]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "JH282-0X0PP-Y8V33-AR1R0-C1VM5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0626",
            "_one_pay" => false,
        ], $result[8]['rows']);

        $this->assertCount(11, $result[9]['rows']);

        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "HJ20K-48LPP-Y8L4A-AJ3UH-2R3L1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[9]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "HJ20K-48LPP-Y8L4A-AJ3UH-2R3L1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[9]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "MN69K-483PP-Y8L42-AHCRK-AXCL1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[9]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "MN69K-483PP-Y8L42-AHCRK-AXCL1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[9]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "M0202-08LYP-A8K4A-A93KP-A81L5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[9]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "M0202-08LYP-A8K4A-A93KP-A81L5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[9]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "H4682-481U4-Y8V42-ACC2P-25915",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[9]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "H4682-481U4-Y8V42-ACC2P-25915",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[9]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "H4290-081UP-A8V4C-ACA0P-AT3L1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[9]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "H4290-081UP-A8V4C-ACA0P-AT3L1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[9]['rows']);
        $this->assertContains([
            "product_no" => "H7J34AC",
            "description" => "HPE Foundation Care 24x7 SVC",
            "serial_no" => null,
            "date_from" => null,
            "date_to" => null,
            "qty" => null,
            "price" => null,
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[9]['rows']);

        $this->assertCount(21, $result[10]['rows']);

        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "H020K-081VP-F8V42-A922H-2X935",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "H020K-081VP-F8V42-A922H-2X935",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "M0210-083Q4-F8V42-AT2UK-C1315",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "M0210-083Q4-F8V42-AT2UK-C1315",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "H0290-481L4-U8K42-A83UP-2ELL5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "H0290-481L4-U8K42-A83UP-2ELL5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "M0692-081LP-Y8K4A-ARCUP-CRTJ5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "M0692-081LP-Y8K4A-ARCUP-CRTJ5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "M4202-48JPP-Y8V4A-A83UK-3XTJ1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "M4202-48JPP-Y8V4A-A83UK-3XTJ1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "HJ20K-48LPP-Y8L4A-AJ3UH-2R3L1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "HJ20K-48LPP-Y8L4A-AJ3UH-2R3L1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "MN69K-483PP-Y8L42-AHCRK-AXCL1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "MN69K-483PP-Y8L42-AHCRK-AXCL1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "M0202-08LYP-A8K4A-A93KP-A81L5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "M0202-08LYP-A8K4A-A93KP-A81L5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "H4682-481U4-Y8V42-ACC2P-25915",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "H4682-481U4-Y8V42-ACC2P-25915",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "H4290-081UP-A8V4C-ACA0P-AT3L1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "H4290-081UP-A8V4C-ACA0P-AT3L1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "H020K-081VP-F8V42-A922H-2X935",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[10]['rows']);

        $this->assertCount(9, $result[11]['rows']);

        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "H020K-081VP-F8V42-A922H-2X935",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[11]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "M0210-083Q4-F8V42-AT2UK-C1315",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[11]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "M0210-083Q4-F8V42-AT2UK-C1315",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[11]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "H0290-481L4-U8K42-A83UP-2ELL5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[11]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "H0290-481L4-U8K42-A83UP-2ELL5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[11]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "M0692-081LP-Y8K4A-ARCUP-CRTJ5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[11]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "M0692-081LP-Y8K4A-ARCUP-CRTJ5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[11]['rows']);
        $this->assertContains([
            "product_no" => "BD715A",
            "description" => "VMw vSphere EntPlus 1P 3yr SW",
            "serial_no" => "M4202-48JPP-Y8V4A-A83UK-3XTJ1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.94",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[11]['rows']);
        $this->assertContains([
            "product_no" => "R1T83A",
            "description" => "VMw vRealize Ops Std /CPU 3yr LTU",
            "serial_no" => "M4202-48JPP-Y8V4A-A83UK-3XTJ1",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "9.92",
            "searchable" => "1086 6360 0856",
            "_one_pay" => false,
        ], $result[11]['rows']);

        $this->assertEmpty($result[12]['rows']);
    }

    protected function pdfParser(): PdfParserInterface
    {
        return app(PdfParserInterface::class);
    }

    /**
     * @group parsing-price-list-docx
     */
    public function test_parses_widex_v2_docx()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/Denmark/Widex V2.docx');

        $result = app(WordParserInterface::class)->parseAsDistributorFile($filePath);

        $this->assertEquals($result[0]['content'], <<<CONTENT
Produktnummer\tBeskrivelse\tSerienummer\tCoverage period from\tCoverage period to\tAntal\tBeløb/DKr\t_one_pay
843374-425\tHPE DL360 Gen9 E5-2620v4 1P 16G Svr/TV\tCZJ7140CBL\t07.06.2020\t\t1\t385,78\t
774435-425\tHP DL360 Gen9 E5-2620v3 SAS EU Svr/TV\tCZJ62708CV\t\t\t1\t385,78\t
843374-425\tHPE DL360 Gen9 E5-2620v4 1P 16G Svr/TV\tCZJ72102LD\t07.06.2020\t23.06.2020\t1\t61 , 37\t
843374-425\tHPE DL360 Gen9 E5-2620v4 1P 16G Svr/TV\tCZJ72102LD\t24.06.2020\t\t1\t385,78\t
704559-421\tHP DL380p Gen8 E5-2630v2 Base EU Svr\tCZ24060X28\t\t\t1\t498,87\t
875839-425\tHPE DL360 Gen10 4114 Svr/TV\tCZJ7450953\t02.12.2020\t09.12.2020\t1\t73,65\t
875839-425\tHPE DL360 Gen10 4114 Svr/TV\tCZJ7450953\t10.12.2020\t\t1\t459,41\t
843374-425\tHPE DL360 Gen9 E5-2620v4 1P 16G Svr/TV\tCZJ7140CBL\t07.06.2020\t\t1\t35,95\t
774435-425\tHP DL360 Gen9 E5-2620v3 SAS EU Svr/TV\tCZJ62708CV\t\t\t1\t39,95\t
843374-425\tHPE DL360 Gen9 E5-2620v4 1P 16G Svr/TV\tCZJ72102LD\t07.06.2020\t\t1\t39,95\t
704559-421\tHP DL380p Gen8 E5-2630v2 Base EU Svr\tCZ24060X28\t\t\t1\t46,47\t
875839-425\tHPE DL360 Gen10 4114 Svr/TV\tCZJ7450953\t02.12.2020\t\t1\t49,97\t
UJ558AC\tHPE Ind Std Svrs Return to HW Supp\t\t\t12.04.2020\t\t1 1 . 879 , 3 0\t1
843374-425\tHPE DL360 Gen9 E5-2620v4 1P 16G Svr/TV\tCZJ7140CBL\t07.04.2017\t06.06.2020\t1\t\t
843374-425\tHPE DL360 Gen9 E5-2620v4 1P 16G Svr/TV\tCZJ72102LD\t24.06.2017\t23.06.2020\t1\t\t
875839-425\tHPE DL360 Gen10 4114 Svr/TV\tCZJ7450953\t10.12.2017\t09.12.2020\t1\t\t
CONTENT
        );
    }

    /**
     * @group parsing-price-list-pdf
     */
    public function test_parses_swcz_sps_hranice_1y_v1_pdf()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/SWCZ-SPS-HRANICE_2020-12-09_1Y_v1.pdf');

        $content = $this->pdfParser()->getText($filePath);

        $result = $this->pdfParser()->parse($content)['pages'];

        $this->assertCount(0, $result[0]['rows']);

        $this->assertCount(3, $result[1]['rows']);

        $this->assertContainsEquals([
            "product_no" => "UJ558AC",
            "description" => "HW RTS Changed",
            "serial_no" => null,
            "date_from" => "28.01.2021",
            "date_to" => null,
            "qty" => null,
            "price" => "5.967,37",
            "searchable" => null,
            "_one_pay" => true,
        ], $result[1]['rows']);

        $this->assertContainsEquals([
            "product_no" => "719064-B21",
            "description" => "HPE DL380 Gen9 8SFF CTO Server",
            "serial_no" => "CZJ53406B9",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "168,30",
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);

        $this->assertContainsEquals([
            "product_no" => "719064-B21",
            "description" => "HPE DL380 Gen9 8SFF CTO Server",
            "serial_no" => "CZJ53406B9",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "1.789,87",
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);
    }

    /**
     * @group parsing-price-list-pdf
     */
    public function test_parses_swcz_sps_hranice_20201209_2y_v1_pdf()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/SWCZ-SPS-HRANICE_2020-12-09_2Y_v1.pdf');

        $content = $this->pdfParser()->getText($filePath);

        $result = $this->pdfParser()->parse($content)['pages'];

        $this->assertCount(0, $result[0]['rows']);

        $this->assertCount(3, $result[1]['rows']);

        $this->assertCount(0, $result[2]['rows']);
        $this->assertCount(0, $result[3]['rows']);

        $this->assertContainsEquals([
            "product_no" => "UJ558AC",
            "description" => "HW RTS Changed",
            "serial_no" => null,
            "date_from" => "28.01.2021",
            "date_to" => null,
            "qty" => null,
            "price" => "5.967,37",
            "searchable" => null,
            "_one_pay" => true,
        ], $result[1]['rows']);

        $this->assertContainsEquals([
            "product_no" => "719064-B21",
            "description" => "HPE DL380 Gen9 8SFF CTO Server",
            "serial_no" => "CZJ53406B9",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "166,60",
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);

        $this->assertContainsEquals([
            "product_no" => "719064-B21",
            "description" => "HPE DL380 Gen9 8SFF CTO Server",
            "serial_no" => "CZJ53406B9",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "1.771,79",
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);
    }

    /**
     * @group parsing-price-list-pdf
     */
    public function test_parses_swcz_nemochice_hodonin_pdf()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/SWCZ-NEMOCNICE HODONIN_2020-12-02_in.disc_v1.pdf');

        $content = $this->pdfParser()->getText($filePath);

        $result = $this->pdfParser()->parse($content);

        $result = $result['pages'];

        $this->assertCount(0, $result[0]['rows']);

        $this->assertCount(6, $result[1]['rows']);

        $this->assertCount(2, $result[2]['rows']);

        $this->assertContainsEquals([
            "product_no" => "H7J32AC",
            "description" => "HPE Foundation Care NBD SVC",
            "serial_no" => null,
            "date_from" => null,
            "date_to" => null,
            "qty" => null,
            "price" => null,
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);

        $this->assertContainsEquals([
            "product_no" => "C8S55A",
            "description" => "HP MSA 2040 SAS DC SFF Storage",
            "serial_no" => "2S6523D350",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "106,25",
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);

        $this->assertContainsEquals([
            "product_no" => "AW594A",
            "description" => "HP P2000 G3 SAS MSA Dual Cntrl SFF Array",
            "serial_no" => "2S6232D065",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "212,50",
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);

        $this->assertContainsEquals([
            "product_no" => "C8S55A",
            "description" => "HP MSA 2040 SAS DC SFF Storage",
            "serial_no" => "2S6523D350",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "106,25",
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);

        $this->assertContainsEquals([
            "product_no" => "AW594A",
            "description" => "HP P2000 G3 SAS MSA Dual Cntrl SFF Array",
            "serial_no" => "2S6232D065",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "2.124,15",
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);

        $this->assertContainsEquals([
            "product_no" => "C8S55A",
            "description" => "HP MSA 2040 SAS DC SFF Storage",
            "serial_no" => "2S6523D350",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "2.442,05",
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);

        $this->assertContainsEquals([
            "product_no" => "UJ559AC",
            "description" => "HPE Storage Return to HW Supp",
            "serial_no" => null,
            "date_from" => "20.01.2021",
            "date_to" => null,
            "qty" => null,
            "price" => "16.743,30",
            "searchable" => null,
            "_one_pay" => true,
        ], $result[2]['rows']);

        $this->assertContainsEquals([
            "product_no" => "AW594A",
            "description" => "HP P2000 G3 SAS MSA Dual Cntrl SFF Array",
            "serial_no" => "2S6232D065",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "212,50",
            "searchable" => null,
            "_one_pay" => false,
        ], $result[2]['rows']);

        $this->assertCount(0, $result[3]['rows']);
    }

    /**
     * @group parsing-price-list-pdf
     */
    public function test_parses_quote_renewal71c896312_pdf()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/quote (renewal) 71-C896312 27.11.2020 0947 [TePr].pdf');

        $content = $this->pdfParser()->getText($filePath);

        $result = $this->pdfParser()->parse($content);

        $result = $result['pages'];

        $this->assertCount(0, $result[0]['rows']);

        $this->assertCount(10, $result[1]['rows']);

        $this->assertContainsEquals([
            "product_no" => "830701-425",
            "description" => "HPE DL20 Gen9 E3-1220v5 NHP EU Svr/TV",
            "serial_no" => "CZ172801BN",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "10.89",
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "830701-425",
            "description" => "HPE DL20 Gen9 E3-1220v5 NHP EU Svr/TV",
            "serial_no" => "CZ172801BR",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "10.89",
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "830701-425",
            "description" => "HPE DL20 Gen9 E3-1220v5 NHP EU Svr/TV",
            "serial_no" => "CZ172801BM",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "10.89",
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "830701-425",
            "description" => "HPE DL20 Gen9 E3-1220v5 NHP EU Svr/TV",
            "serial_no" => "CZ172800WC",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "10.89",
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "830701-425",
            "description" => "HPE DL20 Gen9 E3-1220v5 NHP EU Svr/TV",
            "serial_no" => "CZ172801BN",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "1.98",
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "830701-425",
            "description" => "HPE DL20 Gen9 E3-1220v5 NHP EU Svr/TV",
            "serial_no" => "CZ172801BR",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "1.98",
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "830701-425",
            "description" => "HPE DL20 Gen9 E3-1220v5 NHP EU Svr/TV",
            "serial_no" => "CZ172801BM",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "1.98",
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "830701-425",
            "description" => "HPE DL20 Gen9 E3-1220v5 NHP EU Svr/TV",
            "serial_no" => "CZ172800WC",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "1.98",
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "H7J32AC",
            "description" => "HPE Foundation Care NBD Service",
            "serial_no" => null,
            "date_from" => null,
            "date_to" => null,
            "qty" => null,
            "price" => null,
            "searchable" => null,
            "_one_pay" => false,
        ], $result[1]['rows']);
        $this->assertContainsEquals([
            "product_no" => "UJ558AC",
            "description" => "HPE Ind Std Svrs Return to HW Supp",
            "serial_no" => null,
            "date_from" => "31.12.2020",
            "date_to" => null,
            "qty" => "1",
            "price" => "183.00",
            "searchable" => null,
            "_one_pay" => true,
        ], $result[1]['rows']);
    }

    /**
     * @group parsing-price-list-pdf
     */
    public function test_parses_56946475_1_year_pdf()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/56946475 1 YEAR.pdf');

        $pagesContent = $this->pdfParser()->getText($filePath);

        $result = $this->pdfParser()->parse($pagesContent)['pages'];

        $this->assertCount(5, $result[1]['rows']);
    }

    /**
     * @group parsing-price-list-xlsx
     */
    public function test_parses_support_warehouse_ltd_jbt_foodtech_49766105_09302020_xlsx()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/Support Warehouse Ltd-Jbt Foodtech-49766105-09302020.xlsx');

        $storage = Storage::fake();

        $storage->put($fileName = Str::random(40).'.xlsx', file_get_contents($filePath));

        /** @var QuoteFile $quoteFile */
        $quoteFile = factory(QuoteFile::class)->create([
            'original_file_path' => $fileName,
            'original_file_name' => 'Support Warehouse Ltd-Jbt Foodtech-49766105-09302020.xlsx',
            'file_type' => 'Distributor Price List',
            'pages' => 2,
            'quote_file_format_id' => QuoteFileFormat::value('id'),
            'imported_page' => 2
        ]);

        $excelProcessor = $this->app[EqExcelPriceListProcessor::class];

        $excelProcessor->process($quoteFile);

        $importedRows = $quoteFile->rowsData->pluck('columns_data')->map(fn($row) => collect($row)->pluck('value', 'header')->all())->all();

        $this->assertCount(5, $importedRows);
    }

    /**
     * @group parsing-price-list-xlsx
     */
    public function test_parses_support_warehouse_kromann_reumenrt_xlsx()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/SupportWarehouse - Kromann Reumert.xlsx');

        $storage = Storage::fake();

        $storage->put($fileName = Str::random(40).'.xlsx', file_get_contents($filePath));

        /** @var QuoteFile $quoteFile */
        $quoteFile = factory(QuoteFile::class)->create([
            'original_file_path' => $fileName,
            'original_file_name' => 'SupportWarehouse - Kromann Reumert.xlsx',
            'file_type' => 'Distributor Price List',
            'pages' => 2,
            'quote_file_format_id' => QuoteFileFormat::value('id'),
            'imported_page' => 2
        ]);

        $excelProcessor = $this->app[EqExcelPriceListProcessor::class];

        $excelProcessor->process($quoteFile);

        $importedRows = $quoteFile->rowsData->pluck('columns_data')->map(fn($row) => collect($row)->pluck('value', 'header')->all())->all();

        $this->assertCount(7, $importedRows);
    }

    /**
     * @group parsing-price-list-xlsx
     */
    public function test_parses_support_warehouse_ltd_select_administrative_services_49698055_08272020_xlsx()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/Support Warehouse Ltd-SELECT ADMINISTRATIVE SERVICES-49698055-08272020.xlsx');

        $storage = Storage::fake();

        $storage->put($fileName = Str::random(40).'.xlsx', file_get_contents($filePath));

        /** @var QuoteFile $quoteFile */
        $quoteFile = factory(QuoteFile::class)->create([
            'original_file_path' => $fileName,
            'original_file_name' => 'Support Warehouse Ltd-SELECT ADMINISTRATIVE SERVICES-49698055-08272020.xlsx',
            'file_type' => 'Distributor Price List',
            'pages' => 2,
            'quote_file_format_id' => QuoteFileFormat::value('id'),
            'imported_page' => 2
        ]);

        $excelProcessor = $this->app[EqExcelPriceListProcessor::class];

        $excelProcessor->process($quoteFile);

        $assertRows = [
            [
                'Qty' => 1,
                'Monthly List Price' => 100,
                'Description' => 'HP DL360 Gen9 E5-2630v3 SAS Reman Svr',
                'Reseller cost' => 461.9306000000001,
                'Coverage Period From' => '11/01/2020',
                'Line Item Price' => 563.33,
                'Serial No.' => '2M41539PTP',
                'Product No.' => '755262R-B21',
                'Coverage Period To' => NULL,
            ],
            [
                'Qty' => 1,
                'Monthly List Price' => 100,
                'Description' => 'HPE DL360 Gen9 E5-2630v3 Base SAS Svr',
                'Reseller cost' => 492.00000000000006,
                'Coverage Period From' => NULL,
                'Line Item Price' => 600,
                'Serial No.' => 'MXQ52805JL',
                'Product No.' => '755262-B21',
                'Coverage Period To' => NULL,
            ],
            [
                'Qty' => 1,
                'Monthly List Price' => 133,
                'Description' => 'HP DL380 Gen9 E5-2640v3 US Svr/S-Buy',
                'Reseller cost' => 654.36,
                'Coverage Period From' => NULL,
                'Line Item Price' => 798,
                'Serial No.' => 'MXQ54006RP',
                'Product No.' => '777338-S01',
                'Coverage Period To' => NULL,
            ],
            [
                'Qty' => 1,
                'Monthly List Price' => 100,
                'Description' => 'HPE DL360 Gen9 E5-2630v3 Base SAS Svr',
                'Reseller cost' => 492.00000000000006,
                'Coverage Period From' => NULL,
                'Line Item Price' => 600,
                'Serial No.' => 'MXQ5420101',
                'Product No.' => '755262-B21',
                'Coverage Period To' => NULL,
            ],
            [
                'Qty' => 1,
                'Monthly List Price' => 5,
                'Description' => 'HPE DL360 G9 E5-2609v3 SAS US Svr/S-Buy',
                'Reseller cost' => 24.4606,
                'Coverage Period From' => '10/21/2020',
                'Line Item Price' => 29.83,
                'Serial No.' => 'MXQ53804YL',
                'Product No.' => '780017-S01',
                'Coverage Period To' => NULL,
            ],
            [
                'Qty' => 1,
                'Monthly List Price' => 7,
                'Description' => 'HP DL380 Gen9 E5-2640v3 US Svr/S-Buy',
                'Reseller cost' => 34.440000000000005,
                'Coverage Period From' => NULL,
                'Line Item Price' => 42,
                'Serial No.' => 'MXQ54006RP',
                'Product No.' => '777338-S01',
                'Coverage Period To' => NULL,
            ],
            [
                'Qty' => 1,
                'Monthly List Price' => 6,
                'Description' => 'HP DL360 Gen9 E5-2630v3 SAS Reman Svr',
                'Reseller cost' => 27.716,
                'Coverage Period From' => '11/01/2020',
                'Line Item Price' => 33.8,
                'Serial No.' => '2M41539PTP',
                'Product No.' => '755262R-B21',
                'Coverage Period To' => NULL,
            ],
            [
                'Qty' => 1,
                'Monthly List Price' => 6,
                'Description' => 'HPE DL360 Gen9 E5-2630v3 Base SAS Svr',
                'Reseller cost' => 29.520000000000003,
                'Coverage Period From' => NULL,
                'Line Item Price' => 36,
                'Serial No.' => 'MXQ52805JL',
                'Product No.' => '755262-B21',
                'Coverage Period To' => NULL,
            ],
            [
                'Qty' => 1,
                'Monthly List Price' => 100,
                'Description' => 'HPE DL360 G9 E5-2609v3 SAS US Svr/S-Buy',
                'Reseller cost' => 489.2694,
                'Coverage Period From' => '10/21/2020',
                'Line Item Price' => 596.67,
                'Serial No.' => 'MXQ53804YL',
                'Product No.' => '780017-S01',
                'Coverage Period To' => NULL,
            ],
            [
                'Qty' => 1,
                'Monthly List Price' => 6,
                'Description' => 'HPE DL360 Gen9 E5-2630v3 Base SAS Svr',
                'Reseller cost' => 29.520000000000003,
                'Coverage Period From' => NULL,
                'Line Item Price' => 36,
                'Serial No.' => 'MXQ5420101',
                'Product No.' => '755262-B21',
                'Coverage Period To' => NULL,
            ],
        ];

        $importedRows = $quoteFile->rowsData->pluck('columns_data')->map(fn($row) => collect($row)->pluck('value', 'header')->all())->all();

        $this->assertCount(count($assertRows), $importedRows);

//        foreach ($assertRows as $row) {
//            $this->assertContainsEquals($row, $importedRows);
//        }
    }

    /**
     * @group parsing-price-list-docx
     */
    public function test_parses_renewal_support_warehouse_van_bael_bellis_fc_24x7_docx()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/Renewal Support Warehouse Van Bael  Bellis FC 24x7.docx');

        $pagesResult = $this->wordParser()->parseAsDistributorFile($filePath);

        $this->assertIsArray($pagesResult);

        $lines = preg_split('/\n/', $pagesResult[0]['content']);

        array_shift($lines);

        $rows = collect($lines)->map(fn($line) => array_map(fn($value) => filled($value) ? $value : null, preg_split('/\t/', $line)));

        $filePathCsv = base_path('tests/Unit/Data/distributor-files-test/'.Str::slug('Renewal Support Warehouse Van Bael  Bellis FC 24x7', '-').'.csv');
    }

    protected function wordParser(): WordParserInterface
    {
        return app(WordParserInterface::class);
    }

    /**
     * @group parsing-price-list-pdf
     */
    public function test_parses_supp_inba_1_year_pdf()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/SUPP-INBA_1 year.pdf');

        $pagesContent = $this->pdfParser()->getText($filePath);

        $result = $this->pdfParser()->parse($pagesContent);

        $pagesResult = $result['pages'];

        $pagesWithRows = collect($pagesResult)->filter(fn($page) => filled(array_filter($page['rows'])))->pluck('page');

        $pagesContainLines = [3, 4, 5, 6, 7, 8, 9, 10];

        $pagesWithRows->each(fn($number) => $this->assertContains($number, $pagesContainLines));
    }

    /**
     * @group parsing-price-list-pdf
     */
    public function test_parses_supp_inba2_years_pdf()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/SUPP-INBA_2 years.pdf');

        $pagesContent = $this->pdfParser()->getText($filePath);

        $result = $this->pdfParser()->parse($pagesContent);

        $pagesResult = $result['pages'];

        $lines = collect($pagesResult)->pluck('rows')->collapse();

        $this->assertCount(24, $lines);

        $this->assertContainsEquals([
            "product_no" => "818208-B21",
            "description" => "HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr",
            "serial_no" => "CZJ8170VHN",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "55.00",
            "searchable" => "1086 5193 2310",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "818208-B21",
            "description" => "HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr",
            "serial_no" => "CZJ8170VHT",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "55.00",
            "searchable" => "1086 5193 2310",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "818208-B21",
            "description" => "HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr",
            "serial_no" => "CZJ8170VHN",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "5.00",
            "searchable" => "1086 5193 2310",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "818208-B21",
            "description" => "HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr",
            "serial_no" => "CZJ8170VHT",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "5.00",
            "searchable" => "1086 5193 2310",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "H7J32AC",
            "description" => "HPE Foundation Care NBD SVC",
            "serial_no" => null,
            "date_from" => null,
            "date_to" => null,
            "qty" => null,
            "price" => null,
            "searchable" => "1086 5193 2310",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "UJ558AC",
            "description" => "HPE Ind Std Svrs Return to HW Supp",
            "serial_no" => null,
            "date_from" => "16.09.2020",
            "date_to" => null,
            "qty" => null,
            "price" => "1,963.40",
            "searchable" => "1086 5193 2310",
            "_one_pay" => true,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "818208-B21",
            "description" => "HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr",
            "serial_no" => "CZJ6500J18",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "51.07",
            "searchable" => "1086 5193 2190",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "818208-B21",
            "description" => "HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr",
            "serial_no" => "CZJ6290690",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "51.07",
            "searchable" => "1086 5193 2190",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "818208-B21",
            "description" => "HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr",
            "serial_no" => "CZJ6500J18",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "4.57",
            "searchable" => "1086 5193 2190",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "818208-B21",
            "description" => "HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr",
            "serial_no" => "CZJ6290690",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "4.57",
            "searchable" => "1086 5193 2190",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "H7J32AC",
            "description" => "HPE Foundation Care NBD SVC",
            "serial_no" => null,
            "date_from" => null,
            "date_to" => null,
            "qty" => null,
            "price" => null,
            "searchable" => "1086 5193 2190",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "UJ558AC",
            "description" => "HPE Ind Std Svrs Return to HW Supp",
            "serial_no" => null,
            "date_from" => "16.09.2020",
            "date_to" => null,
            "qty" => null,
            "price" => "837.48",
            "searchable" => "1086 5193 2190",
            "_one_pay" => true,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "818208-B21",
            "description" => "HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr",
            "serial_no" => "CZJ70303XZ",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "60.35",
            "searchable" => "1086 5192 6805",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "818208-B21",
            "description" => "HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr",
            "serial_no" => "CZJ70303Y9",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "60.35",
            "searchable" => "1086 5192 6805",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "818208-B21",
            "description" => "HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr",
            "serial_no" => "CZJ70303XZ",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "5.71",
            "searchable" => "1086 5192 6805",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "818208-B21",
            "description" => "HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr",
            "serial_no" => "CZJ70303Y9",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "5.71",
            "searchable" => "1086 5192 6805",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "H7J32AC",
            "description" => "HPE Foundation Care NBD SVC",
            "serial_no" => null,
            "date_from" => null,
            "date_to" => null,
            "qty" => null,
            "price" => null,
            "searchable" => "1086 5192 6805",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "UJ558AC",
            "description" => "HPE Ind Std Svrs Return to HW Supp",
            "serial_no" => null,
            "date_from" => "16.09.2020",
            "date_to" => null,
            "qty" => null,
            "price" => "635.58",
            "searchable" => "1086 5192 6805",
            "_one_pay" => true,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "818208-B21",
            "description" => "HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr",
            "serial_no" => "CZJ6510640",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "48.78",
            "searchable" => "1086 5192 6745",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "818208-B21",
            "description" => "HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr",
            "serial_no" => "CZJ6510645",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "48.78",
            "searchable" => "1086 5192 6745",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "818208-B21",
            "description" => "HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr",
            "serial_no" => "CZJ6510640",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "4.57",
            "searchable" => "1086 5192 6745",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "818208-B21",
            "description" => "HPE DL360 Gen9 E5-2630v4 1P 16G 8SFF Svr",
            "serial_no" => "CZJ6510645",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "4.57",
            "searchable" => "1086 5192 6745",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "H7J32AC",
            "description" => "HPE Foundation Care NBD SVC",
            "serial_no" => null,
            "date_from" => null,
            "date_to" => null,
            "qty" => null,
            "price" => null,
            "searchable" => "1086 5192 6745",
            "_one_pay" => false,
        ], $lines);

        $this->assertContainsEquals([
            "product_no" => "UJ558AC",
            "description" => "HPE Ind Std Svrs Return to HW Supp",
            "serial_no" => null,
            "date_from" => "16.09.2020",
            "date_to" => null,
            "qty" => null,
            "price" => "569.57",
            "searchable" => "1086 5192 6745",
            "_one_pay" => true,
        ], $lines);


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

    /**
     * @group parsing-price-list-pdf
     */
    public function test_parses_support_warehouse_tata_tryg_d_l380_g92_pdf()
    {
        $filepath = base_path('tests/Unit/Data/distributor-files-test/SupportWarehouse_TATA_Tryg_DL380G9-2.pdf');

        $parser = $this->pdfParser();

        $content = $parser->getText($filepath);

        $result = $parser->parse($content);

        $result = $result['pages'];

        $this->assertCount(2, $result[0]['rows']);
        $this->assertCount(1, $result[1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '719064-B21',
            'description' => 'HPE DL380 Gen9 8SFF CTO Server',
            'serial_no' => 'CZJ7240DB8',
            'date_from' => null,
            'date_to' => null,
            'qty' => '1',
            'price' => '10.121,11',
            'searchable' => null,
            '_one_pay' => false,
        ], $result[0]['rows']);

        $this->assertContainsEquals([
            'product_no' => '719064-B21',
            'description' => 'HPE DL380 Gen9 8SFF CTO Server',
            'serial_no' => 'CZJ7240DB8',
            'date_from' => null,
            'date_to' => null,
            'qty' => '1',
            'price' => '610,15',
            'searchable' => null,
            '_one_pay' => false,
        ], $result[0]['rows']);

        $this->assertContainsEquals([
            'product_no' => 'UJ558AC',
            'description' => 'HPE Ind Std Svrs Return to HW Supp',
            'serial_no' => null,
            'date_from' => '31.08.2020',
            'date_to' => null,
            'qty' => null,
            'price' => '650,91',
            'searchable' => null,
            '_one_pay' => true,
        ], $result[1]['rows']);
    }

    /**
     * @group parsing-price-list-pdf
     */
    public function test_parses_surware_ads_nc_pdf()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/SUPWARE-ADS - NC.pdf');

        $pagesContent = $this->pdfParser()->getText($filePath);

//        static::storeText($filePath, $pagesContent);

        $result = $this->pdfParser()->parse($pagesContent);

        /**
         * The sixh page contain lines without serial number.
         * This case must be handled.
         */
        $page = Arr::first($result['pages'] ?? [], fn($text) => $text['page'] === 6, []);

        $this->assertCount(4, $page['rows']);

        $this->assertContainsEquals([
            "product_no" => "BC745B",
            "description" => "HP 3PAR 7200 OS Suite Base LTU",
            "serial_no" => null,
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.01",
            "searchable" => "1086 5144 4970",
            "_one_pay" => false,
        ], $page['rows']);

        $this->assertContainsEquals([
            "product_no" => "BC746A",
            "description" => "HP 3PAR 7200 OS Suite Drive LTU",
            "serial_no" => null,
            "date_from" => null,
            "date_to" => null,
            "qty" => "12",
            "price" => "8.40",
            "searchable" => "1086 5144 4970",
            "_one_pay" => false,
        ], $page['rows']);

        $this->assertContainsEquals([
            "product_no" => "BC745B",
            "description" => "HP 3PAR 7200 OS Suite Base LTU",
            "serial_no" => null,
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "28.01",
            "searchable" => "1086 5144 4970",
            "_one_pay" => false,
        ], $page['rows']);

        $this->assertContainsEquals([
            "product_no" => "BC746A",
            "description" => "HP 3PAR 7200 OS Suite Drive LTU",
            "serial_no" => null,
            "date_from" => null,
            "date_to" => null,
            "qty" => "12",
            "price" => "8.40",
            "searchable" => "1086 5144 4970",
            "_one_pay" => false,
        ], $page['rows']);
    }

    /**
     * @group parsing-price-list-xlsx
     */
    public function test_parses_317052_support_warehouse_ltd_phlexglobal_l_xlsx()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/317052-Support Warehouse Ltd-Phlexglobal L.xlsx');

        $storage = Storage::fake();

        $storage->put($fileName = Str::random(40).'.xlsx', file_get_contents($filePath));

        /** @var QuoteFile $quoteFile */
        $quoteFile = factory(QuoteFile::class)->create([
            'original_file_path' => $fileName,
            'original_file_name' => '317052-Support Warehouse Ltd-Phlexglobal L.xlsx',
            'file_type' => 'Distributor Price List',
            'pages' => 2,
            'quote_file_format_id' => QuoteFileFormat::value('id'),
            'imported_page' => 2
        ]);

        $excelProcessor = $this->app[EqExcelPriceListProcessor::class];

        $excelProcessor->process($quoteFile);

        $this->assertEquals(44, $quoteFile->rowsData()->count());
    }

    /**
     * @group parsing-price-list-xlsx
     */
    public function test_parses_copy_of_support_warehouse_limited_algonquin_lakeshore_07062020_xlsx()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/Copy of SUPPORT WAREHOUSE LIMITED-ALGONQUIN  LAKESHORE-07062020.xlsx');

        $storage = Storage::fake();

        $storage->put($fileName = Str::random(40).'.xlsx', file_get_contents($filePath));

        /** @var QuoteFile $quoteFile */
        $quoteFile = factory(QuoteFile::class)->create([
            'original_file_path' => $fileName,
            'original_file_name' => 'Copy of SUPPORT WAREHOUSE LIMITED-ALGONQUIN  LAKESHORE-07062020.xlsx',
            'file_type' => 'Distributor Price List',
            'pages' => 2,
            'quote_file_format_id' => QuoteFileFormat::value('id'),
            'imported_page' => 2
        ]);

        $excelProcessor = $this->app[EqExcelPriceListProcessor::class];

        $excelProcessor->process($quoteFile);

        $assertRows = [
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '582633-B21',
                'date_to' => '30/04/2021',
                'description' => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price' => '21.10',
                'serial_no' => null,
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '582633-B21',
                'date_to' => '30/04/2021',
                'description' => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price' => '21.10',
                'serial_no' => null,
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '582633-B21',
                'date_to' => '30/04/2021',
                'description' => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price' => '21.10',
                'serial_no' => null,
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '582633-B21',
                'date_to' => '30/04/2021',
                'description' => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price' => '21.10',
                'serial_no' => null,
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '582633-B21',
                'date_to' => '30/04/2021',
                'description' => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price' => '21.10',
                'serial_no' => null,
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '582633-B21',
                'date_to' => '30/04/2021',
                'description' => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price' => '21.10',
                'serial_no' => null,
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '582633-B21',
                'date_to' => '30/04/2021',
                'description' => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price' => '49.23',
                'serial_no' => null,
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '582633-B21',
                'date_to' => '30/04/2021',
                'description' => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price' => '49.23',
                'serial_no' => null,
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '582633-B21',
                'date_to' => '30/04/2021',
                'description' => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price' => '49.23',
                'serial_no' => null,
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '582633-B21',
                'date_to' => '30/04/2021',
                'description' => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price' => '49.23',
                'serial_no' => null,
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '582633-B21',
                'date_to' => '30/04/2021',
                'description' => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price' => '49.23',
                'serial_no' => null,
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '582633-B21',
                'date_to' => '30/04/2021',
                'description' => 'HP ZMOD ICE 1-SRV ML/DL Bundle',
                'price' => '49.23',
                'serial_no' => null,
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '670633-S01',
                'date_to' => '30/09/2021',
                'description' => 'HP DL360p Gen8 S-Buy E5-2620 Base US Svr',
                'price' => '84.93',
                'serial_no' => 'MXQ3300CHR',
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '670633-S01',
                'date_to' => '30/09/2021',
                'description' => 'HP DL360p Gen8 S-Buy E5-2620 Base US Svr',
                'price' => '849.33',
                'serial_no' => 'MXQ3300CHR',
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '670853-S01',
                'date_to' => '30/09/2021',
                'description' => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price' => '97.07',
                'serial_no' => '2M231601DK',
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '670853-S01',
                'date_to' => '30/09/2021',
                'description' => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price' => '97.07',
                'serial_no' => '2M231601DN',
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '670853-S01',
                'date_to' => '30/09/2021',
                'description' => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price' => '97.07',
                'serial_no' => '2M233402QL',
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '670853-S01',
                'date_to' => '30/09/2021',
                'description' => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price' => '97.07',
                'serial_no' => '2M233402QN',
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '670853-S01',
                'date_to' => '30/09/2021',
                'description' => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price' => '97.07',
                'serial_no' => '2M241301ZP',
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '670853-S01',
                'date_to' => '30/09/2021',
                'description' => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price' => '97.07',
                'serial_no' => '2M241301ZQ',
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '670853-S01',
                'date_to' => '30/09/2021',
                'description' => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price' => '1092.00',
                'serial_no' => '2M231601DK',
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '670853-S01',
                'date_to' => '30/09/2021',
                'description' => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price' => '1092.00',
                'serial_no' => '2M231601DN',
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '670853-S01',
                'date_to' => '30/09/2021',
                'description' => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price' => '1092.00',
                'serial_no' => '2M233402QL',
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '670853-S01',
                'date_to' => '30/09/2021',
                'description' => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price' => '1092.00',
                'serial_no' => '2M233402QN',
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '670853-S01',
                'date_to' => '30/09/2021',
                'description' => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price' => '1092.00',
                'serial_no' => '2M241301ZP',
            ],
            [
                'is_selected' => 0,
                'group_name' => null,
                'date_from' => '01/10/2020',
                'qty' => 1,
                'product_no' => '670853-S01',
                'date_to' => '30/09/2021',
                'description' => 'HP DL380p Gen8 E5-2660 US Svr/S-Buy',
                'price' => '1092.00',
                'serial_no' => '2M241301ZQ',
            ]
        ];

        $this->assertCount(count($assertRows), $quoteFile->rowsData);
    }

    /**
     * @group parsing-price-list-pdf
     */
    public function test_parses_hp_invent_1547101_pdf()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/HPInvent1547101.pdf');

        $pagesContent = $this->pdfParser()->getText($filePath);

        $result = $this->pdfParser()->parse($pagesContent);

        $fourthPage = Arr::first($result['pages'], fn($page) => $page['page'] === 4);

        $this->assertCount(15, $fourthPage['rows']);

        $this->assertContainsEquals([
            "product_no" => "G3J31A",
            "description" => "RHEL Svr 2 Sckt/2 Gst 3yr 9x5 LTU",
            "serial_no" => "PR23LLQLZY",
            "date_from" => "17.01.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "20.67",
            "searchable" => "1086 6358 5045",
            "_one_pay" => false,
        ], $fourthPage['rows']);


        $this->assertContainsEquals([
            "product_no" => "G3J31A",
            "description" => "RHEL Svr 2 Sckt/2 Gst 3yr 9x5 LTU",
            "serial_no" => "PR29P982EJ",
            "date_from" => "17.01.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "20.67",
            "searchable" => "1086 6358 5045",
            "_one_pay" => false,
        ], $fourthPage['rows']);

        $this->assertContainsEquals([
            "product_no" => "G3J31A",
            "description" => "RHEL Svr 2 Sckt/2 Gst 3yr 9x5 LTU",
            "serial_no" => "PR8W20J9Z4",
            "date_from" => "17.01.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "20.67",
            "searchable" => "1086 6358 5045",
            "_one_pay" => false,
        ], $fourthPage['rows']);

        $this->assertContainsEquals([
            "product_no" => "G5J65A",
            "description" => "RHEL Svr 2 Sckt 4 Gst 3yr 9x5 LTU",
            "serial_no" => "PRW73FBMNA",
            "date_from" => "05.01.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "30.60",
            "searchable" => "1086 6358 5045",
            "_one_pay" => false,
        ], $fourthPage['rows']);

        $this->assertContainsEquals([
            "product_no" => "G5J65A",
            "description" => "RHEL Svr 2 Sckt 4 Gst 3yr 9x5 LTU",
            "serial_no" => "PRTJW9P03E",
            "date_from" => "01.02.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "30.60",
            "searchable" => "1086 6358 5045",
            "_one_pay" => false,
        ], $fourthPage['rows']);

        $this->assertContainsEquals([
            "product_no" => "G5J65A",
            "description" => "RHEL Svr 2 Sckt 4 Gst 3yr 9x5 LTU",
            "serial_no" => "PRRS9M9CPA",
            "date_from" => "05.01.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "30.60",
            "searchable" => "1086 6358 5045",
            "_one_pay" => false,
        ], $fourthPage['rows']);

        $this->assertContainsEquals([
            "product_no" => "G5J65A",
            "description" => "RHEL Svr 2 Sckt 4 Gst 3yr 9x5 LTU",
            "serial_no" => "PR97NK7HVU",
            "date_from" => "05.01.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "30.60",
            "searchable" => "1086 6358 5045",
            "_one_pay" => false,
        ], $fourthPage['rows']);

        $this->assertContainsEquals([
            "product_no" => "G3J31A",
            "description" => "RHEL Svr 2 Sckt/2 Gst 3yr 9x5 LTU",
            "serial_no" => "PR4MPYW6EX",
            "date_from" => "01.11.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "20.67",
            "searchable" => "1086 6358 5045",
            "_one_pay" => false,
        ], $fourthPage['rows']);

        $this->assertContainsEquals([
            "product_no" => "G3J31A",
            "description" => "RHEL Svr 2 Sckt/2 Gst 3yr 9x5 LTU",
            "serial_no" => "PRJRTPHCZR",
            "date_from" => "16.11.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "20.67",
            "searchable" => "1086 6358 5045",
            "_one_pay" => false,
        ], $fourthPage['rows']);

        $this->assertContainsEquals([
            "product_no" => "G3J31A",
            "description" => "RHEL Svr 2 Sckt/2 Gst 3yr 9x5 LTU",
            "serial_no" => "PRTP4BC1UX",
            "date_from" => "15.11.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "20.67",
            "searchable" => "1086 6358 5045",
            "_one_pay" => false,
        ], $fourthPage['rows']);

        $this->assertContainsEquals([
            "product_no" => "G3J31A",
            "description" => "RHEL Svr 2 Sckt/2 Gst 3yr 9x5 LTU",
            "serial_no" => "PR54WKSYZ2",
            "date_from" => "15.11.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "20.67",
            "searchable" => "1086 6358 5045",
            "_one_pay" => false,
        ], $fourthPage['rows']);

        $this->assertContainsEquals([
            "product_no" => "G3J31A",
            "description" => "RHEL Svr 2 Sckt/2 Gst 3yr 9x5 LTU",
            "serial_no" => "PRKD32YBEF",
            "date_from" => "17.10.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "20.67",
            "searchable" => "1086 6358 5045",
            "_one_pay" => false,
        ], $fourthPage['rows']);

        $this->assertContainsEquals([
            "product_no" => "G3J31A",
            "description" => "RHEL Svr 2 Sckt/2 Gst 3yr 9x5 LTU",
            "serial_no" => "PR8TKXYVER",
            "date_from" => "15.11.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "20.67",
            "searchable" => "1086 6358 5045",
            "_one_pay" => false,
        ], $fourthPage['rows']);

        $this->assertContainsEquals([
            "product_no" => "G3J31A",
            "description" => "RHEL Svr 2 Sckt/2 Gst 3yr 9x5 LTU",
            "serial_no" => "PR0YXKFHA5",
            "date_from" => "01.02.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "20.67",
            "searchable" => "1086 6358 5045",
            "_one_pay" => false,
        ], $fourthPage['rows']);

        $this->assertContainsEquals([
            "product_no" => "G3J31A",
            "description" => "RHEL Svr 2 Sckt/2 Gst 3yr 9x5 LTU",
            "serial_no" => "PR838FT0ZF",
            "date_from" => "05.01.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "20.67",
            "searchable" => "1086 6358 5045",
            "_one_pay" => false,
        ], $fourthPage['rows']);

        $sixthPage = Arr::first($result['pages'], fn($page) => $page['page'] === 6);

        $this->assertCount(2, $sixthPage['rows']);

        $this->assertContainsEquals([
            'product_no' => 'P9U14A',
            'description' => 'VMw vSOM EntPlus 1P 3yr SW',
            'serial_no' => 'ATEJAETHJEHH',
            'date_from' => null,
            'date_to' => null,
            'qty' => '1',
            'price' => '36.38',
            'searchable' => '1086 6360 0276',
            '_one_pay' => false,
        ], $sixthPage['rows']);

        $this->assertContainsEquals([
            'product_no' => 'P9U41A',
            'description' => 'VMw vCenter Server Std for vSph 3yr SW',
            'serial_no' => 'TUJ9AEGJ3GYC',
            'date_from' => '04.08.2021',
            'date_to' => null,
            'qty' => '1',
            'price' => '48.79',
            'searchable' => '1086 6360 0276',
            '_one_pay' => false,
        ], $sixthPage['rows']);
    }

    /**
     * @group parsing-price-list-pdf
     */
    public function test_hp_invent_0947161_pdf()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/HPInvent0947161.pdf');

        $pagesContent = $this->pdfParser()->getText($filePath);

        $result = $this->pdfParser()->parse($pagesContent);

        $result = $result['pages'];

        $this->assertCount(12, $result[25]['rows']);


        $this->assertContainsEquals([
            "product_no" => "681844-B21",
            "description" => "HP BLc7000 CTO 3 IN LCD Plat Enclosure",
            "serial_no" => "CZJ45101MZ",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "121.00",
            "searchable" => "1086 5485 6896",
            "_one_pay" => false,
        ], $result[25]['rows']);

        $this->assertContainsEquals([
            "product_no" => "AP762A",
            "description" => "HP SB40c w/(4) 300GB SAS SFF Bundle",
            "serial_no" => "SGI129000H",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "52.00",
            "searchable" => "1086 5485 6896",
            "_one_pay" => false,
        ], $result[25]['rows']);

        $this->assertContainsEquals([
            "product_no" => "508664-B21",
            "description" => "HP BLc3000 4 AC-6 Fan Full ICE",
            "serial_no" => "CZ2204023Z",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "149.00",
            "searchable" => "1086 5485 6896",
            "_one_pay" => false,
        ], $result[25]['rows']);

        $this->assertContainsEquals([
            "product_no" => "727021-B21",
            "description" => "HP BL460c Gen9 10Gb/20Gb FLB CTO Blade",
            "serial_no" => "CZ26210354",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "94.00",
            "searchable" => "1086 5485 6896",
            "_one_pay" => false,
        ], $result[25]['rows']);

        $this->assertContainsEquals([
            "product_no" => "AP880A",
            "description" => "HP D2200sb Storage Blade",
            "serial_no" => "TWT234005X",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "113.00",
            "searchable" => "1086 5485 6896",
            "_one_pay" => false,
        ], $result[25]['rows']);

        $this->assertContainsEquals([
            "product_no" => "654081-B21",
            "description" => "HP DL360p Gen8 8-SFF CTO Server",
            "serial_no" => "CZJ43504CB",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "115.00",
            "searchable" => "1086 5485 6896",
            "_one_pay" => false,
        ], $result[25]['rows']);

        $this->assertContainsEquals([
            "product_no" => "494329-B21",
            "description" => "HP OEM DL380G6 CTO Server",
            "serial_no" => "CZ2031B9S5",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "145.00",
            "searchable" => "1086 5485 6896",
            "_one_pay" => false,
        ], $result[25]['rows']);

        $this->assertContainsEquals([
            "product_no" => "813198-B21",
            "description" => "HPE BL460c G9 E5v4 10/20Gb FLB CTO Blade",
            "serial_no" => "CZ2638009H",
            "date_from" => "01.10.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "94.00",
            "searchable" => "1086 5485 6896",
            "_one_pay" => false,
        ], $result[25]['rows']);

        $this->assertContainsEquals([
            "product_no" => "719064-B21",
            "description" => "HPE DL380 Gen9 8SFF CTO Server",
            "serial_no" => "CZJ6370JRD",
            "date_from" => "01.10.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "163.00",
            "searchable" => "1086 5485 6896",
            "_one_pay" => false,
        ], $result[25]['rows']);

        $this->assertContainsEquals([
            "product_no" => "755258-B21",
            "description" => "HP DL360 Gen9 8SFF CTO Server",
            "serial_no" => "CZJ81303R8",
            "date_from" => "27.04.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "73.00",
            "searchable" => "1086 5485 6896",
            "_one_pay" => false,
        ], $result[25]['rows']);

        $this->assertContainsEquals([
            "product_no" => "755258-B21",
            "description" => "HP DL360 Gen9 8SFF CTO Server",
            "serial_no" => "CZJ81303R8",
            "date_from" => "28.04.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "119.00",
            "searchable" => "1086 5485 6896",
            "_one_pay" => false,
        ], $result[25]['rows']);

        $this->assertContainsEquals([
            "product_no" => "H1K93AC",
            "description" => "HPE Proactive Care 24x7 wDMR SVC",
            "serial_no" => null,
            "date_from" => null,
            "date_to" => null,
            "qty" => null,
            "price" => null,
            "searchable" => "1086 5485 6896",
            "_one_pay" => false,
        ], $result[25]['rows']);


        //   26, 26, 27, 29, 31, 34 -- CZJ81303R8

        $rowsCZJ81303R8 = array_filter($result[25]['rows'], fn($row) => $row['serial_no'] === 'CZJ81303R8');

        $this->assertCount(2, $rowsCZJ81303R8);

        $this->assertContainsEquals([
            "product_no" => "755258-B21",
            "description" => "HP DL360 Gen9 8SFF CTO Server",
            "serial_no" => "CZJ81303R8",
            "date_from" => "27.04.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "73.00",
            "searchable" => "1086 5485 6896",
            "_one_pay" => false,
        ], $rowsCZJ81303R8);

        $this->assertContainsEquals([
            "product_no" => "755258-B21",
            "description" => "HP DL360 Gen9 8SFF CTO Server",
            "serial_no" => "CZJ81303R8",
            "date_from" => "28.04.2021",
            "date_to" => null,
            "qty" => "1",
            "price" => "119.00",
            "searchable" => "1086 5485 6896",
            "_one_pay" => false,
        ], $rowsCZJ81303R8);

        $rowsCZJ81303R8 = array_filter($result[26]['rows'], fn($row) => $row['serial_no'] === 'CZJ81303R8');

        $this->assertCount(1, $rowsCZJ81303R8);
        $this->assertContainsEquals([
            "product_no" => "755258-B21",
            "description" => "HP DL360 Gen9 8SFF CTO Server",
            "serial_no" => "CZJ81303R8",
            "date_from" => null,
            "date_to" => null,
            "qty" => "1",
            "price" => "5.00",
            "searchable" => "1086 5485 6896",
            "_one_pay" => false,
        ], $rowsCZJ81303R8);

        $rowsCZJ81303R8 = array_filter($result[28]['rows'], fn($row) => $row['serial_no'] === 'CZJ81303R8');

        $this->assertCount(1, $rowsCZJ81303R8);

        $this->assertContainsEquals([
            "product_no" => "755258-B21",
            "description" => "HP DL360 Gen9 8SFF CTO Server",
            "serial_no" => "CZJ81303R8",
            "date_from" => "28.04.2018",
            "date_to" => "27.04.2021",
            "qty" => "1",
            "price" => null,
            "searchable" => "1086 5485 6896",
            "_one_pay" => false,
        ], $rowsCZJ81303R8);

        $rowsCZJ81303R8 = array_filter($result[30]['rows'], fn($row) => $row['serial_no'] === 'CZJ81303R8');

        $this->assertCount(1, $rowsCZJ81303R8);

        $this->assertContainsEquals([
            "product_no" => "755258-B21",
            "description" => "HP DL360 Gen9 8SFF CTO Server",
            "serial_no" => "CZJ81303R8",
            "date_from" => "28.04.2018",
            "date_to" => "27.04.2021",
            "qty" => "1",
            "price" => null,
            "searchable" => "1086 5485 6896",
            "_one_pay" => false,
        ], $rowsCZJ81303R8);

        $rowsCZJ81303R8 = array_filter($result[33]['rows'], fn($row) => $row['serial_no'] === 'CZJ81303R8');

        $this->assertCount(1, $rowsCZJ81303R8);

        $this->assertContainsEquals([
            "product_no" => "755258-B21",
            "description" => "HP DL360 Gen9 8SFF CTO Server",
            "serial_no" => "CZJ81303R8",
            "date_from" => "28.04.2018",
            "date_to" => "27.04.2021",
            "qty" => "1",
            "price" => null,
            "searchable" => "1086 5485 6896",
            "_one_pay" => false,
        ], $rowsCZJ81303R8);
    }

    /**
     * @group parsing-price-list-xlsx
     */
    public function test_parses_copy_of_eu_q_00034206_2021_05_19_price_mubea_hzp_sro_xlsx()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/Copy of EU_Q-00034206_2021-05-19_Price_MUBEA - HZP S.R.O.xlsx');

        $storage = Storage::fake();

        $storage->put($fileName = Str::random(40).'.xlsx', file_get_contents($filePath));

        /** @var QuoteFile $quoteFile */
        $quoteFile = factory(QuoteFile::class)->create([
            'original_file_path' => $fileName,
            'original_file_name' => 'Copy of EU_Q-00034206_2021-05-19_Price_MUBEA - HZP S.R.O.xlsx',
            'file_type' => 'Distributor Price List',
            'pages' => 2,
            'quote_file_format_id' => QuoteFileFormat::value('id'),
            'imported_page' => 2
        ]);

        $excelProcessor = $this->app[EqExcelPriceListProcessor::class];

        $excelProcessor->process($quoteFile);

        $headers = $quoteFile->rowsData->pluck('columns_data.*.header')->collapse()->unique()->values();

        $this->assertCount(16, $headers);

        $this->assertCount(5, $quoteFile->rowsData);
    }

    /**
     * @group parsing-price-list-xlsx
     */
    public function test_parses_disti_epd_almac_7200_value_seller_end_12_31_2021_5yr_24x7_sv351964_xlsx()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/Disti - EPD - Almac 7200 - ValueSeller end 12-31-2021 - 5yr 24x7 - SV351964.xlsx');

        $rows = $this->app[ExcelPriceListReader::class]->readFile($filePath);

        $this->assertCount(9, iterator_to_array($rows));
    }

    /**
     * @group parsing-price-list-xlsx
     */
    public function test_parses_copy_of_quote_501_sq637159_1_uk_1yr_lenovo_xlsx()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/Copy of Quote-501-SQ637159-1 - UK 1Yr Lenovo.xlsx');

        $rows = $this->app[ExcelPriceListReader::class]->readFile($filePath);

        $this->assertCount(138, iterator_to_array($rows));
    }

    /**
     * @group parsing-price-list-pdf
     */
    public function test_parses_autocont_pro_eurowag_062021_list_v2_pdf()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/Autocont pro Eurowag_062021_LIST_v2.pdf');

        $parser = $this->app[\App\Contracts\Services\PdfParserInterface::class];

        $fileContent = $parser->getText($filePath);
        $fileData = $parser->parse($fileContent);

        $this->assertEmpty($fileData['pages'][0]['rows']);

        $this->assertCount(19, $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '804326-B21',
            'description' => 'HPE Smart Array E208i-a SR Gen10 Ctrlr',
            'serial_no' => 'PEYHB0ARH830JA',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '835955-B21',
            'description' => 'HPE 16GB 2Rx8 PC4-2666V-R Smart Kit',
            'serial_no' => 'RWEZNAVTGAH03R',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '835955-B21',
            'description' => 'HPE 16GB 2Rx8 PC4-2666V-R Smart Kit',
            'serial_no' => 'RWEZNAVTGAH03S',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '835955-B21',
            'description' => 'HPE 16GB 2Rx8 PC4-2666V-R Smart Kit',
            'serial_no' => 'RWEZNAVTGAH03T',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '835955-B21',
            'description' => 'HPE 16GB 2Rx8 PC4-2666V-R Smart Kit',
            'serial_no' => 'RWEZNAVTGAH03V',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '860657-B21',
            'description' => 'HPE DL360 Gen10 Xeon-S 4114 Kit',
            'serial_no' => 'AWCTN0AK8A91B3',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '860657-L21',
            'description' => 'HPE DL360 Gen10 Xeon-S 4114 FIO Kit',
            'serial_no' => 'EWCTK04J2811TF',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '865414-B21',
            'description' => 'HPE 800W FS Plat Ht Plg LH Pwr Sply Kit',
            'serial_no' => '5WBXT0B4DA61YJ',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '726537-B21',
            'description' => 'HP 9.5mm SATA DVD-RW Jb Gen9 Kit',
            'serial_no' => NULL,
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '804326-B21',
            'description' => 'HPE Smart Array E208i-a SR Gen10 Ctrlr',
            'serial_no' => 'PEYHB0ARH830JA',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '835955-B21',
            'description' => 'HPE 16GB 2Rx8 PC4-2666V-R Smart Kit',
            'serial_no' => 'RWEZNAVTGAH03R',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '835955-B21',
            'description' => 'HPE 16GB 2Rx8 PC4-2666V-R Smart Kit',
            'serial_no' => 'RWEZNAVTGAH03S',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '835955-B21',
            'description' => 'HPE 16GB 2Rx8 PC4-2666V-R Smart Kit',
            'serial_no' => 'RWEZNAVTGAH03T',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '835955-B21',
            'description' => 'HPE 16GB 2Rx8 PC4-2666V-R Smart Kit',
            'serial_no' => 'RWEZNAVTGAH03V',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '860657-B21',
            'description' => 'HPE DL360 Gen10 Xeon-S 4114 Kit',
            'serial_no' => 'AWCTN0AK8A91B3',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '860657-L21',
            'description' => 'HPE DL360 Gen10 Xeon-S 4114 FIO Kit',
            'serial_no' => 'EWCTK04J2811TF',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '865414-B21',
            'description' => 'HPE 800W FS Plat Ht Plg LH Pwr Sply Kit',
            'serial_no' => '5WBXT0B4DA61YJ',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => '865414-B21',
            'description' => 'HPE 800W FS Plat Ht Plg LH Pwr Sply Kit',
            'serial_no' => NULL,
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);

        $this->assertContainsEquals([
            'product_no' => 'H7J35AC',
            'description' => 'HPE Foundation Care 24x7 wDMR SVC',
            'serial_no' => NULL,
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => NULL,
            'price' => NULL,
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][1]['rows']);


        $this->assertCount(8, $fileData['pages'][2]['rows']);

        $this->assertContainsEquals([
            'product_no' => '867959-B21',
            'description' => 'HPE DL360 Gen10 8SFF CTO Server',
            'serial_no' => 'MXQ80700YR',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '3.008,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][2]['rows']);

        $this->assertContainsEquals([
            'product_no' => '867959-B21',
            'description' => 'HPE DL360 Gen10 8SFF CTO Server',
            'serial_no' => 'MXQ80700YR',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '201,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][2]['rows']);

        $this->assertContainsEquals([
            'product_no' => '875483-B21',
            'description' => 'HPE 240GB SATA MU SFF SC DS SSD',
            'serial_no' => 'UWEZF01ZR96IWT',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][2]['rows']);

        $this->assertContainsEquals([
            'product_no' => '875483-B21',
            'description' => 'HPE 240GB SATA MU SFF SC DS SSD',
            'serial_no' => 'UWEZF01ZR983U5',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][2]['rows']);

        $this->assertContainsEquals([
            'product_no' => '874543-B21',
            'description' => 'HPE 1U Gen10 SFF Easy Install Rail Kit',
            'serial_no' => NULL,
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][2]['rows']);

        $this->assertContainsEquals([
            'product_no' => '875483-B21',
            'description' => 'HPE 240GB SATA MU SFF SC DS SSD',
            'serial_no' => 'UWEZF01ZR96IWT',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][2]['rows']);

        $this->assertContainsEquals([
            'product_no' => '875483-B21',
            'description' => 'HPE 240GB SATA MU SFF SC DS SSD',
            'serial_no' => 'UWEZF01ZR983U5',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][2]['rows']);

        $this->assertContainsEquals([
            'product_no' => 'BD505A',
            'description' => 'HPE iLO Adv 1-svr Lic 3yr Support',
            'serial_no' => NULL,
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '107,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][2]['rows']);


        $this->assertCount(17, $fileData['pages'][3]['rows']);

        $this->assertContainsEquals([
            'product_no' => '875483-B21',
            'description' => 'HPE 240GB SATA MU SFF SC DS SSD',
            'serial_no' => 'UWEZF01ZR96KJ9',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][3]['rows']);

        $this->assertContainsEquals([
            'product_no' => '875483-B21',
            'description' => 'HPE 240GB SATA MU SFF SC DS SSD',
            'serial_no' => 'UWEZF01ZR98708',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][3]['rows']);

        $this->assertContainsEquals([
            'product_no' => '872479-B21',
            'description' => 'HPE 1.2TB SAS 10K SFF SC DS HDD',
            'serial_no' => '2WJNQ0183ABAQ0',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][3]['rows']);

        $this->assertContainsEquals([
            'product_no' => '872479-B21',
            'description' => 'HPE 1.2TB SAS 10K SFF SC DS HDD',
            'serial_no' => '2WJNQ0183ABB14',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][3]['rows']);

        $this->assertContainsEquals([
            'product_no' => '872479-B21',
            'description' => 'HPE 1.2TB SAS 10K SFF SC DS HDD',
            'serial_no' => '2WJNQ0183ABB18',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][3]['rows']);

        $this->assertContainsEquals([
            'product_no' => '872479-B21',
            'description' => 'HPE 1.2TB SAS 10K SFF SC DS HDD',
            'serial_no' => '2WJNQ0183ABB2Q',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][3]['rows']);

        $this->assertContainsEquals([
            'product_no' => '872479-B21',
            'description' => 'HPE 1.2TB SAS 10K SFF SC DS HDD',
            'serial_no' => '2WJNQ0183ABB2S',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][3]['rows']);

        $this->assertContainsEquals([
            'product_no' => '872479-B21',
            'description' => 'HPE 1.2TB SAS 10K SFF SC DS HDD',
            'serial_no' => '2WJNQ0183ABB3J',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][3]['rows']);

        $this->assertContainsEquals([
            'product_no' => '875483-B21',
            'description' => 'HPE 240GB SATA MU SFF SC DS SSD',
            'serial_no' => 'UWEZF01ZR96KJ9',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][3]['rows']);

        $this->assertContainsEquals([
            'product_no' => '875483-B21',
            'description' => 'HPE 240GB SATA MU SFF SC DS SSD',
            'serial_no' => 'UWEZF01ZR98708',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][3]['rows']);

        $this->assertContainsEquals([
            'product_no' => '872479-B21',
            'description' => 'HPE 1.2TB SAS 10K SFF SC DS HDD',
            'serial_no' => '2WJNQ0183ABAQ0',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][3]['rows']);

        $this->assertContainsEquals([
            'product_no' => '872479-B21',
            'description' => 'HPE 1.2TB SAS 10K SFF SC DS HDD',
            'serial_no' => '2WJNQ0183ABB14',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][3]['rows']);

        $this->assertContainsEquals([
            'product_no' => '872479-B21',
            'description' => 'HPE 1.2TB SAS 10K SFF SC DS HDD',
            'serial_no' => '2WJNQ0183ABB18',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][3]['rows']);

        $this->assertContainsEquals([
            'product_no' => '872479-B21',
            'description' => 'HPE 1.2TB SAS 10K SFF SC DS HDD',
            'serial_no' => '2WJNQ0183ABB2Q',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][3]['rows']);

        $this->assertContainsEquals([
            'product_no' => '872479-B21',
            'description' => 'HPE 1.2TB SAS 10K SFF SC DS HDD',
            'serial_no' => '2WJNQ0183ABB2S',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][3]['rows']);

        $this->assertContainsEquals([
            'product_no' => '872479-B21',
            'description' => 'HPE 1.2TB SAS 10K SFF SC DS HDD',
            'serial_no' => '2WJNQ0183ABB3J',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][3]['rows']);

        $this->assertContainsEquals([
            'product_no' => 'H7J35AC',
            'description' => 'HPE Foundation Care 24x7 wDMR SVC',
            'serial_no' => NULL,
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => NULL,
            'price' => NULL,
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][3]['rows']);


        $this->assertCount(38, $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '875966-B21',
            'description' => 'HPE DL360 Gen10 TAA 8SFF CTO Svr',
            'serial_no' => 'MXQ80702G9',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '3.008,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '875966-B21',
            'description' => 'HPE DL360 Gen10 TAA 8SFF CTO Svr',
            'serial_no' => 'MXQ80702G9',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '201,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '865414-B21',
            'description' => 'HPE 800W FS Plat Ht Plg LH Pwr Sply Kit',
            'serial_no' => '5WEBP0B8JA7217',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '865414-B21',
            'description' => 'HPE 800W FS Plat Ht Plg LH Pwr Sply Kit',
            'serial_no' => '5WEBP0B8JA721F',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '860691-L21',
            'description' => 'HPE DL360 Gen10 Xeon-G 6136 FIO Kit',
            'serial_no' => 'TWGHTAVVXAH019',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '860691-B21',
            'description' => 'HPE DL360 Gen10 Xeon-G 6136 Kit',
            'serial_no' => 'AWCTN0AK8A71T2',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07N',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07O',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07P',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07Q',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07R',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07S',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07T',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07U',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07V',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07W',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07X',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07Y',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '804331-B21',
            'description' => 'HPE Smart Array P408i-a SR Gen10 Ctrlr',
            'serial_no' => 'PEYHC0CRHAF1LH',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '865414-B21',
            'description' => 'HPE 800W FS Plat Ht Plg LH Pwr Sply Kit',
            'serial_no' => '5WEBP0B8JA7217',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '865414-B21',
            'description' => 'HPE 800W FS Plat Ht Plg LH Pwr Sply Kit',
            'serial_no' => '5WEBP0B8JA721F',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '860691-L21',
            'description' => 'HPE DL360 Gen10 Xeon-G 6136 FIO Kit',
            'serial_no' => 'TWGHTAVVXAH019',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '860691-B21',
            'description' => 'HPE DL360 Gen10 Xeon-G 6136 Kit',
            'serial_no' => 'AWCTN0AK8A71T2',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07N',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07O',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07P',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07Q',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07R',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07S',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07T',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07U',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07V',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07W',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07X',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '815098-B21',
            'description' => 'HPE 16GB 1Rx4 PC4-2666V-R Smart Kit',
            'serial_no' => 'RVYDUAVTHAH07Y',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '804331-B21',
            'description' => 'HPE Smart Array P408i-a SR Gen10 Ctrlr',
            'serial_no' => 'PEYHC0CRHAF1LH',
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '726537-B21',
            'description' => 'HP 9.5mm SATA DVD-RW Jb Gen9 Kit',
            'serial_no' => NULL,
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);

        $this->assertContainsEquals([
            'product_no' => '874543-B21',
            'description' => 'HPE 1U Gen10 SFF Easy Install Rail Kit',
            'serial_no' => NULL,
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '0,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][4]['rows']);


        $this->assertCount(2, $fileData['pages'][5]['rows']);

        $this->assertContainsEquals([
            'product_no' => 'UJ558AC',
            'description' => 'HPE Ind Std Svrs Return to HW Supp',
            'serial_no' => NULL,
            'date_from' => '06.07.2021',
            'date_to' => NULL,
            'qty' => NULL,
            'price' => '4.579,29',
            'searchable' => NULL,
            '_one_pay' => true,
        ], $fileData['pages'][5]['rows']);

        $this->assertContainsEquals([
            'product_no' => 'BD505A',
            'description' => 'HPE iLO Adv 1-svr Lic 3yr Support',
            'serial_no' => NULL,
            'date_from' => NULL,
            'date_to' => NULL,
            'qty' => '1',
            'price' => '107,00',
            'searchable' => NULL,
            '_one_pay' => false,
        ], $fileData['pages'][5]['rows']);


        $this->assertEmpty($fileData['pages'][6]['rows']);
    }

    /**
     * @group parsing-price-list-pdf
     */
    public function test_parses_5356429813_acr_4hr_exch_pdf()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/5356429813 ACR 4HR EXCH.PDF');

        $parser = $this->app[\App\Contracts\Services\PdfParserInterface::class];

        $fileContent = $parser->getText($filePath);
        $fileData = $parser->parse($fileContent);

        $this->assertEmpty($fileData['pages'][0]['rows']);

        $this->assertCount(22, $fileData['pages'][1]['rows']);
        $this->assertCount(50, $fileData['pages'][2]['rows']);
        $this->assertCount(49, $fileData['pages'][3]['rows']);
        $this->assertCount(50, $fileData['pages'][4]['rows']);
        $this->assertCount(34, $fileData['pages'][5]['rows']);
        $this->assertCount(50, $fileData['pages'][6]['rows']);
        $this->assertCount(49, $fileData['pages'][7]['rows']);
        $this->assertCount(44, $fileData['pages'][8]['rows']);
        $this->assertCount(50, $fileData['pages'][9]['rows']);
        $this->assertCount(49, $fileData['pages'][10]['rows']);
        $this->assertCount(50, $fileData['pages'][11]['rows']);
        $this->assertCount(14, $fileData['pages'][12]['rows']);
        $this->assertEmpty($fileData['pages'][13]['rows']);
    }

    /**
     * @group parsing-price-list-xlsx
     */
    public function test_parses_support_warehouse_ltd_the_sea_ranch_association_07012021_xlsx()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/Support Warehouse Ltd-The Sea Ranch Association-07012021.xlsx');

        $rows = $this->app[ExcelPriceListReader::class]->readFile($filePath);

        /** @var Row[] $rows */
        $rows = iterator_to_array($rows);

        $this->assertCount(20, $rows);

        $headingRowMapping = $rows[0]->getHeadingRow()->getMapping();

        $this->assertContains('Product No.', $headingRowMapping);
        $this->assertContains('Description', $headingRowMapping);
        $this->assertContains('Serial No.', $headingRowMapping);
        $this->assertContains('Coverage Period from:', $headingRowMapping);
        $this->assertContains('to:', $headingRowMapping);
        $this->assertContains('Qty', $headingRowMapping);
        $this->assertContains('Monthly List Price', $headingRowMapping);
        $this->assertContains('Line Item Price', $headingRowMapping);
        $this->assertContains('Reseller cost', $headingRowMapping);
    }

    /**
     * @group parsing-price-list-docx
     */
    public function test_parses_renewal_support_warehouse_acromet_doc_docx()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/Renewal Support Warehouse Arcomet.doc.docx');

        /** @var WordParserInterface $wordParser */
        $wordParser = $this->app[WordParserInterface::class];

        $data = $wordParser->parseAsDistributorFile($filePath);

        $this->assertNotEmpty($data);

        $this->assertIsArray($data[0]);
        $this->assertArrayHasKey('content', $data[0]);

        $expectedContent = <<<DATA
Product Nu\tDescription\tSerial Number\tCoverage period from\tCoverage period to\tQty\tPrice/EUR\t_one_pay
P02467-B21\tHPE DL380 Gen10 4208 1P 32G 24SFF Svr\tCZ292004XR\t\t13.06.2022\t1\t15,00\t
P02467-B21\tHPE DL380 Gen10 4208 1P 32G 24SFF Svr\tCZ292004XR\t14.06.2022\t\t1\t96,00\t
704558-421\tHP DL380p Gen8 E5-2650v2 25SFF EU Svr\tCZ25030779\t\t\t1\t80,00\t
704558-421\tHP DL380p Gen8 E5-2650v2 25SFF EU Svr\tCZ25030778\t\t\t1\t80,00\t
P02467-B21\tHPE DL380 Gen10 4208 1P 32G 24SFF Svr\tCZ292004XR\t\t\t1\t11,00\t
704558-421\tHP DL380p Gen8 E5-2650v2 25SFF EU Svr\tCZ25030779\t\t\t1\t7,00\t
704558-421\tHP DL380p Gen8 E5-2650v2 25SFF EU Svr\tCZ25030778\t\t\t1\t7,00\t
P02467-B21\tHPE DL380 Gen10 4208 1P 32G 24SFF Svr\tCZ292004XR\t15.05.2019\t13.06.2022\t1\t\t
DATA;

        $this->assertSame($expectedContent, $data[0]['content']);

        // Ensure a state of the class is immutable.
        $data = $wordParser->parseAsDistributorFile($filePath);

        $this->assertNotEmpty($data);

        $this->assertIsArray($data[0]);
        $this->assertArrayHasKey('content', $data[0]);
        $this->assertSame($expectedContent, $data[0]['content']);
    }

    /**
     * @group parsing-price-list-xlsx
     */
    public function test_parses_said_108692709740_lafarge_south_africa_q_57357090()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/SAID - 108692709740 - LAFARGE SOUTH AFRICA - Q 57357090.xlsx');

        $rows = $this->app[ExcelPriceListReader::class]->readFile($filePath);

        /** @var Row[] $rows */
        $rows = iterator_to_array($rows);

        $this->assertNotEmpty($rows);
    }

    /**
     * @group parsing-price-list-xlsx
     */
    public function test_parses_support_warehouse_ltd_windes_mcclaughry_71306096_08182021()
    {
        $filePath = base_path('tests/Unit/Data/distributor-files-test/Support Warehouse Ltd-WINDES & MCCLAUGHRY-71306096-08182021.xlsx');

        $rows = $this->app[ExcelPriceListReader::class]->readFile($filePath);

        /** @var Row[] $rows */
        $rows = iterator_to_array($rows);

        $this->assertNotEmpty($rows);
    }
}
