<?php

return [
    'pdftotext' => [
        'bin_path' => env('PDF_PARSER_DEFAULT_BIN')
            ? '/usr/bin/pdftotext'
            : base_path('xpdf-tools/bin64/pdftotext'),
    ]
];
