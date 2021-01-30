<?php

namespace Tests\Unit\Parser;

use App\Models\QuoteFile\QuoteFile;
use App\Services\DocumentProcessor\EasyQuote\PaymentPDF as EQPaymentPDF;
use App\Services\DocumentProcessor\DocumentEngine\PaymentPDF as DEPaymentPDF;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentPDFHandlerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test easyQuote handler continuously processes pages of payment pdf file.
     *
     * @return void
     */
    public function testEqHandlerContinuouslyProcessesPaymentPdfFile()
    {
        $storage = Storage::persistentFake();

        $fileName = Str::random(40).'.pdf';
        $storage->put($fileName, file_get_contents(base_path('tests/Unit/Data/schedule-files-test/SW-PROTI_20210107_9438.pdf')));

        $quoteFile = new QuoteFile();
        $quoteFile->imported_page = 5;
        $quoteFile->original_file_path = $fileName;
        $quoteFile->save();

        $this->app[EQPaymentPDF::class]->process($quoteFile);

        $this->assertCount(20, $quoteFile->scheduleData->value);

        $this->assertContainsEquals([
            'from' => '11.01.2021',
            'to' => '10.04.2021',
            'price' => 2006.54,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.04.2021',
            'to' => '10.07.2021',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.07.2021',
            'to' => '10.10.2021',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.10.2021',
            'to' => '10.01.2022',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.01.2022',
            'to' => '10.04.2022',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.04.2022',
            'to' => '10.07.2022',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.07.2022',
            'to' => '10.10.2022',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.10.2022',
            'to' => '10.01.2023',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.01.2023',
            'to' => '10.04.2023',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.04.2023',
            'to' => '10.07.2023',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.07.2023',
            'to' => '10.10.2023',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.10.2023',
            'to' => '10.01.2024',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.01.2024',
            'to' => '10.04.2024',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.04.2024',
            'to' => '10.07.2024',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.07.2024',
            'to' => '10.10.2024',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.10.2024',
            'to' => '10.01.2025',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.01.2025',
            'to' => '10.04.2025',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.04.2025',
            'to' => '10.07.2025',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.07.2025',
            'to' => '10.10.2025',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);
        $this->assertContainsEquals([
            'from' => '11.10.2025',
            'to' => '10.01.2026',
            'price' => 1125.09,
        ], $quoteFile->scheduleData->value);

    }

    /**
     * Test Document Engine handler continuously process pages of payment pdf file.
     *
     * @return void
     */
    public function testDeHandlerContinuouslyProcessesPaymentPdfFile()
    {
        $this->markTestSkipped();

        $this->app[Config::class]->set('services.document_api.url', 'http://18.134.146.232:1337');

        $storage = Storage::persistentFake();

        $fileName = Str::random(40).'.pdf';
        $storage->put($fileName, file_get_contents(base_path('tests/Unit/Data/schedule-files-test/SW-PROTI_20210107_9438.pdf')));

        $quoteFile = new QuoteFile();
        $quoteFile->imported_page = 5;
        $quoteFile->original_file_path = $fileName;
        $quoteFile->save();

        $this->app[DEPaymentPDF::class]->process($quoteFile);
    }
}
