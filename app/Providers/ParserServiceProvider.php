<?php

namespace App\Providers;

use App\Contracts\Services\{CsvParserInterface,
    ManagesDocumentProcessors,
    PdfParserInterface,
    ProcessesQuoteFile,
    WordParserInterface};
use App\Services\{CsvParser,
    DocumentProcessor\DocumentEngine\DePdfRescuePaymentScheduleProcessor,
    DocumentProcessor\DocumentEngine\DePdfRescuePriceListProcessor,
    DocumentProcessor\DocumentEngine\DePdfWorldwidePriceListProcessor,
    DocumentProcessor\DocumentEngine\DeWordRescuePriceListProcessor,
    DocumentProcessor\DocumentProcessor,
    DocumentProcessor\EasyQuote\EqCsvRescuePriceListProcessor,
    DocumentProcessor\EasyQuote\EqExcelPriceListProcessor,
    DocumentProcessor\EasyQuote\EqExcelRescuePaymentScheduleProcessor,
    DocumentProcessor\EasyQuote\EqPdfRescuePaymentScheduleProcessor,
    DocumentProcessor\EasyQuote\EqPdfRescuePriceListProcessor,
    DocumentProcessor\EasyQuote\EqWordRescuePriceListProcessor,
    PdfParser\PdfParser,
    WordParser};
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ParserServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
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
        ], ProcessesQuoteFile::class);

        $this->app->singleton(ManagesDocumentProcessors::class, function (Container $container) {

            $processor = $container->make(DocumentProcessor::class);

            $this->registerDrivers($processor);

            return $processor;

        });

        $this->app->when(DePdfRescuePaymentScheduleProcessor::class)->needs(ProcessesQuoteFile::class)->give(EqPdfRescuePaymentScheduleProcessor::class);

        $this->app->when(DePdfRescuePriceListProcessor::class)->needs(ProcessesQuoteFile::class)->give(EqPdfRescuePriceListProcessor::class);

        $this->app->when(DeWordRescuePriceListProcessor::class)->needs(ProcessesQuoteFile::class)->give(EqWordRescuePriceListProcessor::class);

        $this->app->when(DePdfWorldwidePriceListProcessor::class)->needs(ProcessesQuoteFile::class)->give(EqPdfRescuePriceListProcessor::class);
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
