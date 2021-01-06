<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Services\{CsvParserInterface, ManagesDocumentProcessors, PdfParserInterface, WordParserInterface};
use App\Services\{CsvParser, DocumentProcessor\DocumentProcessor, PdfParser\PdfParser, WordParser};
use Illuminate\Contracts\Container\Container;

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

        $this->app->singleton(ManagesDocumentProcessors::class, function () {
            $processor = $this->app->make(DocumentProcessor::class);
            
            $this->registerDrivers($processor);

            return $processor;
        });
    }

    protected function registerDrivers(DocumentProcessor $documentProcessor)
    {
        $deParameters = $this->app->config['docprocessor.document_engine_parameters'];
        $defaultDrivers = $this->app->config['docprocessor.default_drivers'];
        $deDrivers = $this->app->config['docprocessor.document_engine_drivers'];
        
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
 