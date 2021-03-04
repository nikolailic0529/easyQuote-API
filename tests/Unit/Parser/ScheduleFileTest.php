<?php

namespace Tests\Unit\Parser;

use App\Contracts\Services\PdfParserInterface;
use Tests\TestCase;

class ScheduleFileTest extends TestCase
{
    /**
     * Test parses ZQMN 57097547.pdf
     *
     * @return void
     */
    public function test_parses_zqmn_57097547_pdf()
    {
        $filePath = base_path('tests/Unit/Data/schedule-files-test/ZQMN 57097547.pdf');

        $page = $this->app[PdfParserInterface::class]->getText($filePath)[3];

        $result = $this->app[PdfParserInterface::class]->parseSchedule($page);

        $this->assertCount(2, $result);

        $this->assertContainsEquals([
            'from' => '01.03.2021',
            'to' => '28.02.2022',
            'price' => 460.44
        ], $result);

        $this->assertContainsEquals([
            'from' => '01.03.2022',
            'to' => '28.02.2023',
            'price' => 460.44
        ], $result);
    }


     /**
     * Test parses SWCZ-Nemocnice Sumperk-3Y_v1.pdf
     *
     * @return void
     */
    public function test_parses_swcz_nemocnice_sumperk_3y_pdf()
    {
        $filePath = base_path('tests/Unit/Data/schedule-files-test/SWCZ-Nemocnice Sumperk-3Y_v1.pdf');

        $content = $this->app[PdfParserInterface::class]->getText($filePath);

        $lastPageContent = $content[2]['content'];

        $result = $this->app[PdfParserInterface::class]->parseSchedule(
            $content[2]
        );

        $this->assertCount(3, $result);

        $this->assertContainsEquals([
            "from" => "01.02.2021",
            "to" => "31.01.2022",
            "price" => 71.7876,
        ], $result);

        $this->assertContainsEquals([
            "from" => "01.02.2022",
            "to" => "31.01.2023",
            "price" => 71.7876,
        ], $result);

        $this->assertContainsEquals([
            "from" => "01.02.2023",
            "to" => "31.01.2024",
            "price" => 71.7876,
        ], $result);
    }

    /**
     * Test parses SWCZ-Nemocnice Sumperk-4Y_v1.pdf
     *
     * @return void
     */
    public function test_parses_swcz_nemocnice_sumperk_4y_pdf()
    {
        $filePath = base_path('tests/Unit/Data/schedule-files-test/SWCZ-Nemocnice Sumperk-4Y_v1.pdf');

        $content = $this->app[PdfParserInterface::class]->getText($filePath);

        $lastPageContent = $content[2]['content'];

        $result = $this->app[PdfParserInterface::class]->parseSchedule(
            $content[2]
        );

        $this->assertCount(4, $result);

        $this->assertContainsEquals([
            "from" => "01.02.2021",
            "to" => "31.01.2022",
            "price" => 71.7876,
        ], $result);

        $this->assertContainsEquals([
            "from" => "01.02.2022",
            "to" => "31.01.2023",
            "price" => 71.7876,
        ], $result);

        $this->assertContainsEquals([
            "from" => "01.02.2023",
            "to" => "31.01.2024",
            "price" => 71.7876,
        ], $result);

        $this->assertContainsEquals([
            "from" => "01.02.2024",
            "to" => "31.01.2025",
            "price" => 71.7876,
        ], $result);
    }

    /**
     * Test parses SWCZ-Nemocnice Sumperk-5Y_v1.pdf
     *
     * @return void
     */
    public function test_parses_swcz_nemocnice_sumperk_5y_pdf()
    {
        $filePath = base_path('tests/Unit/Data/schedule-files-test/SWCZ-Nemocnice Sumperk-5Y_v1.pdf');

        $content = $this->app[PdfParserInterface::class]->getText($filePath);

        $lastPageContent = $content[2]['content'];

        $result = $this->app[PdfParserInterface::class]->parseSchedule(
            $content[2]
        );

        $this->assertCount(5, $result);

        $this->assertContainsEquals([
            "from" => "01.02.2021",
            "to" => "31.01.2022",
            "price" => 71.7876,
        ], $result);

        $this->assertContainsEquals([
            "from" => "01.02.2022",
            "to" => "31.01.2023",
            "price" => 71.7876,
        ], $result);

        $this->assertContainsEquals([
            "from" => "01.02.2023",
            "to" => "31.01.2024",
            "price" => 71.7876,
        ], $result);

        $this->assertContainsEquals([
            "from" => "01.02.2024",
            "to" => "31.01.2025",
            "price" => 71.7876,
        ], $result);

        $this->assertContainsEquals([
            "from" => "01.02.2025",
            "to" => "31.01.2026",
            "price" => 71.7876,
        ], $result);
    }

}
