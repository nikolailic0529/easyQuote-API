<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Services\{CsvParserInterface, ParserServiceInterface, PdfParserInterface, WordParserInterface};
use App\Services\{CsvParser, ParserService, PdfParser\PdfParser, WordParser};

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

        $this->app->singleton(ParserServiceInterface::class, ParserService::class);
    }

    public function provides()
    {
        return [
            ParserServiceInterface::class,
            WordParserInterface::class,
            PdfParserInterface::class,
            CsvParserInterface::class,
        ];
    }
}
