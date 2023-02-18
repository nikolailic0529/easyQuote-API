<?php

namespace App\Domain\DocumentProcessing\Providers;

use App\Domain\DocumentEngine\ParserClientFactory;
use App\Domain\DocumentProcessing\Contracts\ManagesDocumentProcessors;
use App\Domain\DocumentProcessing\Contracts\PdfParserInterface;
use App\Domain\DocumentProcessing\Contracts\ProcessesQuoteFile;
use App\Domain\DocumentProcessing\Contracts\{WordParserInterface};
use App\Domain\DocumentProcessing\DocumentEngine\DeExcelPriceListProcessor;
use App\Domain\DocumentProcessing\DocumentEngine\DePdfRescuePaymentScheduleProcessor;
use App\Domain\DocumentProcessing\DocumentEngine\DePdfRescuePriceListProcessor;
use App\Domain\DocumentProcessing\DocumentEngine\DePdfWorldwidePriceListProcessor;
use App\Domain\DocumentProcessing\DocumentEngine\DeWordRescuePriceListProcessor;
use App\Domain\DocumentProcessing\DocumentProcessor;
use App\Domain\DocumentProcessing\EasyQuote\EqCsvRescuePriceListProcessor;
use App\Domain\DocumentProcessing\EasyQuote\EqExcelPriceListProcessor;
use App\Domain\DocumentProcessing\EasyQuote\EqExcelRescuePaymentScheduleProcessor;
use App\Domain\DocumentProcessing\EasyQuote\EqPdfRescuePaymentScheduleProcessor;
use App\Domain\DocumentProcessing\EasyQuote\EqPdfRescuePriceListProcessor;
use App\Domain\DocumentProcessing\EasyQuote\EqWordRescuePriceListProcessor;
use App\Domain\DocumentProcessing\Readers\Contracts\CsvParserInterface;
use App\Domain\DocumentProcessing\Readers\Csv\CsvParser;
use App\Domain\DocumentProcessing\Readers\Pdf\PdfParser;
use App\Domain\DocumentProcessing\Readers\Word\WordParser;
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\ServiceProvider;

class ParserServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(\Spatie\PdfToText\Pdf::class)->needs('$binPath')->give(fn () => $this->app['config']->get('pdfparser.pdftotext.bin_path'));

        $this->app->singleton(WordParserInterface::class, WordParser::class);

        $this->app->singleton(PdfParserInterface::class, PdfParser::class);

        $this->app->singleton(CsvParserInterface::class, CsvParser::class);

        $this->app->tag([
            EqCsvRescuePriceListProcessor::class,
            EqExcelPriceListProcessor::class,
            EqExcelRescuePaymentScheduleProcessor::class,
            EqPdfRescuePriceListProcessor::class,
            EqPdfRescuePaymentScheduleProcessor::class,
            EqWordRescuePriceListProcessor::class,

            DePdfRescuePaymentScheduleProcessor::class,
            DePdfRescuePriceListProcessor::class,
            DePdfWorldwidePriceListProcessor::class,
            DeWordRescuePriceListProcessor::class,
            DeExcelPriceListProcessor::class,
        ], ProcessesQuoteFile::class);

        $this->app->singleton(ManagesDocumentProcessors::class, function (Container $container) {
            $processor = $container->make(DocumentProcessor::class);

            $this->registerDrivers($processor);

            return $processor;
        });

        $this->app->singleton(ParserClientFactory::class, function (Container $container) {
            return new ParserClientFactory(
                config: $container['config'],
                logger: $container['log']->channel('document-processor')
            );
        });

        $this->app->when(DePdfRescuePaymentScheduleProcessor::class)->needs(ProcessesQuoteFile::class)->give(EqPdfRescuePaymentScheduleProcessor::class);

        $this->app->when(DePdfRescuePriceListProcessor::class)->needs(ProcessesQuoteFile::class)->give(EqPdfRescuePriceListProcessor::class);

        $this->app->when(DeWordRescuePriceListProcessor::class)->needs(ProcessesQuoteFile::class)->give(EqWordRescuePriceListProcessor::class);

        $this->app->when(DePdfWorldwidePriceListProcessor::class)->needs(ProcessesQuoteFile::class)->give(EqPdfRescuePriceListProcessor::class);

        $this->app->when(DeExcelPriceListProcessor::class)->needs(ProcessesQuoteFile::class)->give(EqExcelPriceListProcessor::class);

        $this->app->when(DeExcelPriceListProcessor::class)->needs(FilesystemAdapter::class)->give(function (Container $container) {
            return $container['filesystem']->disk();
        });
    }

    protected function registerDrivers(ManagesDocumentProcessors $documentProcessor)
    {
        $defaultDrivers = $this->app['config']['docprocessor.default_drivers'];
        $deDrivers = $this->app['config']['docprocessor.document_engine_drivers'];

        // Register default document drivers.
        foreach ($defaultDrivers as $name => $concrete) {
            $documentProcessor->extend($name, function (Container $container) use ($concrete) {
                return $container->make($concrete);
            });
        }

        // Register document engine drivers.
        foreach ($deDrivers as $name => $concrete) {
            $documentProcessor->extend($name, function (Container $container) use ($concrete) {
                return $container->make($concrete);
            });
        }
    }

    public function provides()
    {
        return [
            WordParserInterface::class,
            PdfParserInterface::class,
            CsvParserInterface::class,
            ManagesDocumentProcessors::class,
        ];
    }
}
