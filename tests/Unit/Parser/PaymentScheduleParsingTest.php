<?php

namespace Tests\Unit\Parser;

use App\Models\QuoteFile\QuoteFile;
use App\Services\DocumentProcessor\EasyQuote\Parsers\ExcelPaymentScheduleParser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;

/**
 * @group build
 */
class PaymentScheduleParsingTest extends ParsingTest
{
    use DatabaseTransactions;

    /**
     * Test an ability to process Austria payment schedule files.
     */
    public function test_it_processes_austria_payment_schedule_files(): void
    {
        $this->processFilesFromDir('Austria');
    }

    /**
     * Test an ability to process France payment schedule files.
     */
    public function test_it_processes_france_payment_schedule_files(): void
    {
        $this->processFilesFromDir('France');
    }

    /**
     * Test an ability to process United Kingdom payment schedule files.
     */
    public function test_it_processes_united_kingdom_payment_schedule_files(): void
    {
        $this->processFilesFromDir('UK');
    }

    /**
     * Test an ability to process United States payment schedule files.
     */
    public function test_it_processes_united_states_payment_schedule_files(): void
    {
        $this->processFilesFromDir('USA');
    }

    /**
     * Test an ability to process Nederland payment schedule files.
     */
    public function test_it_processes_nederland_payment_schedule_files(): void
    {
        $this->processFilesFromDir('Nederland');
    }

    /**
     * Test an ability to parse excel payment schedule using internal implementation of parser.
     */
    public function test_it_parses_new_support_warehouse_ltd_kensico_capital_management_01172020_xlsx_using_internal_parser(
    ): void
    {
        $file = new \SplFileInfo(base_path('tests/Unit/Data/schedule-files-test/USA/New Support Warehouse Ltd-Kensico Capital Management-01172020.xlsx'));

        $data = $this->app[ExcelPaymentScheduleParser::class]->parse($file, 6);

        $this->assertCount(3, $data);

        $array = $data->toArray();

        /** @var list<array{from: string, to: string, price: string}> $expectedPayments */
        $expectedPayments = [
            [
                'from' => '1/11/2020',
                'to' => '1/10/2021',
                'price' => '9991.2169415948',
            ],
            [
                'from' => '1/11/2021',
                'to' => '1/10/2022',
                'price' => '9152.7365292026',
            ],
            [
                'from' => '1/11/2022',
                'to' => '1/10/2023',
                'price' => '9152.7365292026',
            ],
        ];

        foreach ($expectedPayments as $payment) {
            $this->assertContainsEquals($payment, $array);
        }
    }

    /**
     * Test an ability to parse excel payment schedule using internal implementation of parser.
     */
    public function test_it_parses_copy_of_support_warehouse_ltd_cable_one_it_012920192_xlsx_using_internal_parser(
    ): void
    {
        $file = new \SplFileInfo(base_path('tests/Unit/Data/schedule-files-test/UK/Copy of Support Warehouse Ltd-Cable One IT-012920192.xlsx'));

        $data = $this->app[ExcelPaymentScheduleParser::class]->parse($file, 4);

        $this->assertCount(5, $data);

        $array = $data->toArray();

        /** @var list<array{from: string, to: string, price: string}> $expectedPayments */
        $expectedPayments = [
            [
                'from' => '02/10/2019',
                'to' => '02/09/2020',
                'price' => '8172.65',
            ],
            [
                'from' => '02/10/2020',
                'to' => '02/09/2021',
                'price' => '8409.42',
            ],
            [
                'from' => '02/10/2021',
                'to' => '02/09/2022',
                'price' => '11526.33',
            ],
            [
                'from' => '02/10/2022',
                'to' => '02/09/2023',
                'price' => '11526.33',
            ],
            [
                'from' => '02/10/2023',
                'to' => '02/09/2024',
                'price' => '11526.33',
            ],
        ];

        foreach ($expectedPayments as $payment) {
            $this->assertContainsEquals($payment, $array);
        }
    }

    protected function fileType(): string
    {
        return QFT_PS;
    }

    protected function filesDirPath(): string
    {
        return base_path('tests/Unit/Data/schedule-files-test');
    }

    protected function performFileAssertions(QuoteFile $quoteFile): void
    {
        $this->assertNotEmpty($quoteFile->scheduleData->value, $quoteFile->original_file_path);

        $expectedData = $this->resolveAttributeFromAssertMapping('data', $quoteFile->original_file_name);

        $actualData = $quoteFile->scheduleData->value;

        $this->assertCount(count($expectedData), $actualData);

        foreach ($expectedData as $i => $payment) {
            $this->assertArrayHasKey($i, $actualData);
            $this->assertEquals($payment, $actualData[$i]);
        }
    }

    protected function assertionMapping(): Collection
    {
        return collect(
            json_decode(
                file_get_contents(base_path('tests/Unit/Data/schedule-files-test/mapping.json')),
                associative: true
            )
        );
    }
}
