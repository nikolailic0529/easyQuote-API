<?php namespace App\Services;

use App\Contracts \ {
    Services\ParserServiceInterface,
    Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumn,
    Repositories\QuoteFile\QuoteFileRepositoryInterface as QuoteFileRepository
};
use Illuminate\Support\Collection;
use Smalot\PdfParser\Parser;
use App\Models\QuoteFile \ {
    QuoteFile
};
use Storage;

class ParserService implements ParserServiceInterface
{
    protected $parser;

    protected $importableColumn;

    public function __construct(
        QuoteFileRepository $quoteFile,
        ImportableColumn $importableColumn,
        Parser $parser
    ) {
        $this->quoteFile = $quoteFile;
        $this->importableColumn = $importableColumn;
        $this->parser = $parser;
    }

    public function handle(QuoteFile $quoteFile)
    {   
        if($quoteFile->handled_at) {
            return response()->json([
                'message' => __('This Quote File has been already handled')
            ]);
        };

        $fileFormat = $quoteFile->format->extension;

        if($fileFormat !== 'pdf') {
            return response()->json([
                'message' => __('This file format is not available for handling yet')
            ], 415);
        };
        
        $pdfTextPages = $this->getPdfText($quoteFile);

        $this->quoteFile->createRawData(
            $quoteFile,
            $pdfTextPages
        );

        $rawData = $this->quoteFile->getRawData($quoteFile);

        return $this->quoteFile->createColumnData(
            $quoteFile,
            $this->parsePdfText(
                $rawData->content
            ),
            $rawData->page
        );
    }

    public function getPdfText(QuoteFile $quoteFile)
    {
        $filePath = Storage::path($quoteFile->original_file_path);

        $document = $this->parser->parseFile($filePath);
        
        $rawPages = collect();

        collect($document->getPages())->each(function ($page, $key) use ($rawPages) {
            $text = str_replace("\0", "", $page->getText());

            $rawPages->push([
                'page' => ++$key,
                'content' => $text
            ]);
        });

        return $rawPages->toArray();
    }

    public function parsePdfText(String $text)
    {
        $regexpColumns = $this->importableColumn->allColumnsRegs();

        $regexp = $regexpColumns->implode('');
        $regexp = "/^{$regexp}$/mu";

        preg_match_all($regexp, $text, $matches, PREG_UNMATCHED_AS_NULL);

        $columnsAliases = $this->importableColumn->allColumnsAliases();

        $matches = collect($matches)->only(
            $columnsAliases
        );

        return $matches->toArray();
    }
}
