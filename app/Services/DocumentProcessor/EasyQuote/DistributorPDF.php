<?php

namespace App\Services\DocumentProcessor\EasyQuote;

use App\Contracts\Services\PdfParserInterface;
use App\Contracts\Services\ProcessesQuoteFile;
use App\Models\QuoteFile\QuoteFile;
use App\Services\DocumentProcessor\EasyQuote\Concerns\UpdatesDistributorFileData;
use Illuminate\Support\Facades\Storage;

class DistributorPDF implements ProcessesQuoteFile
{
    use UpdatesDistributorFileData;

    protected PdfParserInterface $parser;

    public function __construct(PdfParserInterface $parser)
    {
        $this->parser = $parser;
    }

    public function process(QuoteFile $quoteFile)
    {
        $rawData = $this->parser->getText(Storage::path($quoteFile->original_file_path));

        ['pages' => $pages, 'attributes' => $attributes] = $this->parser->parse($rawData);

        $this->updateDistributorQuoteFileData($quoteFile, $pages, $attributes);
    }
}
