<?php

return [

    'document_engine_parameters' => env('DE_DOCUMENT_PARAMETERS'),

    'default_drivers' => [

        'distributor_price_list_pdf' => App\Services\DocumentProcessor\EasyQuote\EqPdfRescuePriceListProcessor::class,
        'worldwide_distributor_price_list_pdf' => \App\Services\DocumentProcessor\EasyQuote\EqPdfRescuePriceListProcessor::class,
        'distributor_price_list_excel' => App\Services\DocumentProcessor\EasyQuote\EqExcelPriceListProcessor::class,
        'worldwide_distributor_price_list_excel' => \App\Services\DocumentProcessor\EasyQuote\EqExcelPriceListProcessor::class,
        'distributor_price_list_csv' => App\Services\DocumentProcessor\EasyQuote\EqCsvRescuePriceListProcessor::class,
        'distributor_price_list_word' => App\Services\DocumentProcessor\EasyQuote\EqWordRescuePriceListProcessor::class,
        'payment_schedule_pdf' => App\Services\DocumentProcessor\EasyQuote\EqPdfRescuePaymentScheduleProcessor::class,
        'payment_schedule_excel' => App\Services\DocumentProcessor\EasyQuote\EqExcelRescuePaymentScheduleProcessor::class,

    ],

    'document_engine_drivers' => [

        'worldwide_distributor_price_list_pdf' => \App\Services\DocumentProcessor\DocumentEngine\DePdfWorldwidePriceListProcessor::class,
        'distributor_price_list_pdf' => App\Services\DocumentProcessor\DocumentEngine\DePdfRescuePriceListProcessor::class,
        'distributor_price_list_word' => App\Services\DocumentProcessor\DocumentEngine\DeWordRescuePriceListProcessor::class,

    ],

];
