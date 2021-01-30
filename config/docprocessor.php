<?php

return [

    'document_engine_parameters' => env('DE_DOCUMENT_PARAMETERS'),

    'default_drivers' => [

        'distributor_price_list_pdf' => App\Services\DocumentProcessor\EasyQuote\DistributorPDF::class,
        'distributor_price_list_excel' => App\Services\DocumentProcessor\EasyQuote\DistributorExcel::class,
        'distributor_price_list_csv' => App\Services\DocumentProcessor\EasyQuote\DistributorCSV::class,
        'distributor_price_list_word' => App\Services\DocumentProcessor\EasyQuote\DistributorWord::class,
        'payment_schedule_pdf' => App\Services\DocumentProcessor\EasyQuote\PaymentPDF::class,
        'payment_schedule_excel' => App\Services\DocumentProcessor\EasyQuote\PaymentExcel::class,

    ],

    'document_engine_drivers' => [

        'distributor_price_list_pdf' => App\Services\DocumentProcessor\DocumentEngine\DistributorPDF::class,
        'distributor_price_list_word' => App\Services\DocumentProcessor\DocumentEngine\DistributorWord::class,

    ],

];
