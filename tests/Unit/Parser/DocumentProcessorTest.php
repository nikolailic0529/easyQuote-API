<?php

namespace Tests\Unit\Parser;

use App\Contracts\Services\ManagesDocumentProcessors;
use App\Contracts\Services\ProcessesQuoteFile;
use App\Services\DocumentProcessor\DocumentEngine\DeExcelPriceListProcessor;
use App\Services\DocumentProcessor\DocumentEngine\DePdfRescuePriceListProcessor;
use App\Services\DocumentProcessor\DocumentEngine\DePdfWorldwidePriceListProcessor;
use App\Services\DocumentProcessor\DocumentEngine\DeWordRescuePriceListProcessor;
use Tests\TestCase;

class DocumentProcessorTest extends TestCase
{
    /**
     * Test DocumentProcessor manager creates document engine processor for pdf rescue price list file.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testItCreatesDocumentEngineProcessorForPdfRescuePriceListFile()
    {
        /** @var ManagesDocumentProcessors $documentProcessor */
        $documentProcessor = $this->app->make(ManagesDocumentProcessors::class);

        $this->app['config']['docprocessor.document_engine_enabled'] = true;

        $driver = $documentProcessor->driver('distributor_price_list_pdf');

        $this->assertInstanceOf(ProcessesQuoteFile::class, $driver);
        $this->assertInstanceOf(DePdfRescuePriceListProcessor::class, $driver);
    }

    /**
     * Test DocumentProcessor manager creates document engine processor for pdf worldwide price list file.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testItCreatesDocumentEngineProcessorForPdfWorldwidePriceListFile()
    {
        /** @var ManagesDocumentProcessors $documentProcessor */
        $documentProcessor = $this->app->make(ManagesDocumentProcessors::class);

        $this->app['config']['docprocessor.document_engine_enabled'] = true;

        $driver = $documentProcessor->driver('worldwide_distributor_price_list_pdf');

        $this->assertInstanceOf(ProcessesQuoteFile::class, $driver);
        $this->assertInstanceOf(DePdfWorldwidePriceListProcessor::class, $driver);
    }

    /**
     * Test DocumentProcessor manager creates document engine processor for excel rescue price list file.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testItCreatesDocumentEngineProcessorForExcelRescuePriceListFile()
    {
        /** @var ManagesDocumentProcessors $documentProcessor */
        $documentProcessor = $this->app->make(ManagesDocumentProcessors::class);

        $this->app['config']['docprocessor.document_engine_enabled'] = true;

        $driver = $documentProcessor->driver('distributor_price_list_excel');

        $this->assertInstanceOf(ProcessesQuoteFile::class, $driver);
        $this->assertInstanceOf(DeExcelPriceListProcessor::class, $driver);
    }

    /**
     * Test DocumentProcessor manager creates document engine processor for excel worldwide price list file.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testItCreatesDocumentEngineProcessorForExcelWorldwidePriceListFile()
    {
        /** @var ManagesDocumentProcessors $documentProcessor */
        $documentProcessor = $this->app->make(ManagesDocumentProcessors::class);

        $this->app['config']['docprocessor.document_engine_enabled'] = true;

        $driver = $documentProcessor->driver('distributor_price_list_excel');

        $this->assertInstanceOf(ProcessesQuoteFile::class, $driver);
        $this->assertInstanceOf(DeExcelPriceListProcessor::class, $driver);
    }

    /**
     * Test DocumentProcessor manager creates document engine processor for word rescue price list file.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testItCreatesDocumentEngineProcessorForWordRescuePriceListFile()
    {
        /** @var ManagesDocumentProcessors $documentProcessor */
        $documentProcessor = $this->app->make(ManagesDocumentProcessors::class);

        $this->app['config']['docprocessor.document_engine_enabled'] = true;

        $driver = $documentProcessor->driver('distributor_price_list_word');

        $this->assertInstanceOf(ProcessesQuoteFile::class, $driver);
        $this->assertInstanceOf(DeWordRescuePriceListProcessor::class, $driver);
    }
}
