<?php

return [
    'pdftotext' => [
        'linux' => '/bin/pdftotext',
        'win' => 'Services/PdfParser/bin/pdftotext.exe',
        'default_bin' => env('PDF_PARSER_DEFAULT_BIN', false)
    ]
];
