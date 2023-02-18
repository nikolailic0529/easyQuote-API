<?php

return [
    'document_engine_enabled' => env('DOCUMENT_ENGINE_ENABLED', true),

    'default_drivers' => [
        'distributor_price_list_pdf' => App\Domain\DocumentProcessing\EasyQuote\EqPdfRescuePriceListProcessor::class,
        'worldwide_distributor_price_list_pdf' => App\Domain\DocumentProcessing\EasyQuote\EqPdfRescuePriceListProcessor::class,
        'distributor_price_list_excel' => App\Domain\DocumentProcessing\EasyQuote\EqExcelPriceListProcessor::class,
        'worldwide_distributor_price_list_excel' => App\Domain\DocumentProcessing\EasyQuote\EqExcelPriceListProcessor::class,
        'distributor_price_list_csv' => App\Domain\DocumentProcessing\EasyQuote\EqCsvRescuePriceListProcessor::class,
        'distributor_price_list_word' => App\Domain\DocumentProcessing\EasyQuote\EqWordRescuePriceListProcessor::class,
        'payment_schedule_pdf' => App\Domain\DocumentProcessing\EasyQuote\EqPdfRescuePaymentScheduleProcessor::class,
        'payment_schedule_excel' => App\Domain\DocumentProcessing\EasyQuote\EqExcelRescuePaymentScheduleProcessor::class,
    ],

    'document_engine_drivers' => [
        'worldwide_distributor_price_list_pdf' => App\Domain\DocumentProcessing\DocumentEngine\DePdfWorldwidePriceListProcessor::class,
        'distributor_price_list_pdf' => App\Domain\DocumentProcessing\DocumentEngine\DePdfRescuePriceListProcessor::class,
        'distributor_price_list_word' => App\Domain\DocumentProcessing\DocumentEngine\DeWordRescuePriceListProcessor::class,
        'distributor_price_list_excel' => App\Domain\DocumentProcessing\DocumentEngine\DeExcelPriceListProcessor::class,
        'worldwide_distributor_price_list_excel' => App\Domain\DocumentProcessing\DocumentEngine\DeExcelPriceListProcessor::class,
    ],
];
