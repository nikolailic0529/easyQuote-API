<?php

namespace App\Domain\DocumentProcessing\EasyQuote;

use App\Domain\DocumentProcessing\Contracts\PdfParserInterface;
use App\Domain\DocumentProcessing\Contracts\ProcessesQuoteFile;
use App\Domain\DocumentProcessing\EasyQuote\Concerns\UpdatesDistributorFileData;
use App\Domain\QuoteFile\Models\QuoteFile;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class EqPdfRescuePriceListProcessor implements ProcessesQuoteFile
{
    use UpdatesDistributorFileData;

    protected PdfParserInterface $parser;

    public function __construct(PdfParserInterface $parser)
    {
        $this->parser = $parser;
    }

    public function process(QuoteFile $quoteFile): void
    {
        $rawData = $this->parser->getText(Storage::path($quoteFile->original_file_path));

        ['pages' => $pages, 'attributes' => $attributes] = $this->parser->parse($rawData);

        $this->updateDistributorQuoteFileData($quoteFile, $pages, $attributes);
    }

    public static function getProcessorUuid(): UuidInterface
    {
        return Uuid::fromString('b873a78e-d950-438e-b8c6-fb791582cf98');
    }
}
