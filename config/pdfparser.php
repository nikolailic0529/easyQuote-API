<?php

return [
    'pdftotext' => [
        'bin_path' => env('PDF_PARSER_DEFAULT_BIN')
            ? '/usr/bin/pdftotext'
            : (windows_os() ? app_path('Services/PdfParser/bin/pdftotext.exe') : '/bin/pdftotext'),
    ]
];
