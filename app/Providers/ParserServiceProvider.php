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
    DocumentProcessor\DocumentEngine\DeWordRescuePriceListProcessor,
    DocumentProcessor\DocumentProcessor,
    DocumentProcessor\EasyQuote\EqPdfRescuePaymentScheduleProcessor,
    DocumentProcessor\EasyQuote\EqPdfRescuePriceListProcessor,
    DocumentProcessor\EasyQuote\EqWordRescuePriceListProcessor,
    PdfParser\PdfParser,
    WordParser};
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

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

        $this->app->singleton(ManagesDocumentProcessors::class, function (Container $container) {

            $processor = $container->make(DocumentProcessor::class);

            $this->registerDrivers($processor);

            return $processor;

        });

        $this->app->when(DePdfRescuePaymentScheduleProcessor::class)->needs(ProcessesQuoteFile::class)->give(EqPdfRescuePaymentScheduleProcessor::class);

        $this->app->when(DePdfRescuePriceListProcessor::class)->needs(ProcessesQuoteFile::class)->give(EqPdfRescuePriceListProcessor::class);

        $this->app->when(DeWordRescuePriceListProcessor::class)->needs(ProcessesQuoteFile::class)->give(EqWordRescuePriceListProcessor::class);
    }

    protected function registerDrivers(ManagesDocumentProcessors $documentProcessor)
    {
        $deParameters = $this->app['config']['docprocessor.document_engine_parameters'];
        $defaultDrivers = $this->app['config']['docprocessor.default_drivers'];
        $deDrivers = $this->app['config']['docprocessor.document_engine_drivers'];

        // Register default document drivers.
        foreach ($defaultDrivers as $name => $concrete) {
            $documentProcessor->extend($name, function (Container $container) use ($concrete) {
                return $container->make($concrete);
            });
        }

        // Register Document Engine drivers when they are set.
        if (!is_null($deParameters)) {
            foreach (explode(',', $deParameters) as $name) {
                if (isset($deDrivers[$name])) {
                    $concrete = $deDrivers[$name];

                    $documentProcessor->extend($name, function (Container $container) use ($concrete) {
                        return $container->make($concrete);
                    });
                }
            }
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
