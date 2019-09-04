<?php namespace App\Services;

use App\Contracts \ {
    Services\ParserServiceInterface,
    Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumn,
    Repositories\QuoteFile\QuoteFileRepositoryInterface as QuoteFileRepository
};
use Illuminate\Support\Collection;
use Smalot\PdfParser\Parser as PdfParser;
use League\Csv \ {
    Reader as CsvParser,
    Exception as CsvParserException
};
use App\Models\QuoteFile \ {
    QuoteFile
};
use Storage;

class ParserService implements ParserServiceInterface
{
    protected $pdfParser;

    protected $importableColumn;

    public function __construct(
        QuoteFileRepository $quoteFile,
        ImportableColumn $importableColumn,
        PdfParser $pdfParser
    ) {
        $this->quoteFile = $quoteFile;
        $this->importableColumn = $importableColumn;
        $this->pdfParser = $pdfParser;
    }

    public function handle(QuoteFile $quoteFile)
    {   
        if($quoteFile->isHandled()) {
            return response()->json([
                'message' => __('This Quote File has been already handled')
            ]);
        };

        return $this->routeParser($quoteFile);
    }

    public function routeParser(QuoteFile $quoteFile)
    {
        $fileFormat = $quoteFile->format->extension;

        switch ($fileFormat) {
            case 'pdf':
                return $this->handlePdf($quoteFile);
                break;
            case 'csv':
                return $this->handleCsv($quoteFile);
            default:
                return response()->json([
                    'message' => __('This file format is not available for handling')
                ], 415);
                break;
        }
    }

    public function handlePdf(QuoteFile $quoteFile)
    {
        $rawPages = $this->getPdfText($quoteFile);

        $this->quoteFile->createRawData(
            $quoteFile,
            $rawPages
        );

        $rawData = $this->quoteFile->getRawData($quoteFile);

        $parsedData = $this->parsePdfText($rawData->content);

        return $this->quoteFile->createColumnData(
            $quoteFile,
            $parsedData,
            $rawData->page
        );
    }

    public function handleCsv(QuoteFile $quoteFile)
    {
        $rawData = $this->getCsvText($quoteFile);

        $parsedData = $this->parseCsvText($rawData, $quoteFile);

        return $this->quoteFile->createColumnData(
            $quoteFile,
            $parsedData
        );
    }

    public function getCsvText(QuoteFile $quoteFile)
    {
        $content = mb_convert_encoding(Storage::get($quoteFile->original_file_path), 'UTF-8', 'UTF-8');
        
        return $content;
    }

    public function parseCsvText(String $content, QuoteFile $quoteFile)
    {
        $document = CsvParser::createFromString($content);
        
        $document->setHeaderOffset(0);

        $separator = $quoteFile->dataSelectSeparator->separator;

        $document->setDelimiter($separator);

        try {
            $records = collect($document);
        } catch (CsvParserException $exception) {
            return response()->json([
                'message' => __('Please set the headers in the CSV file')
            ], 415);
        }

        $parsedData = [];

        foreach ($records as $record) {
            foreach ($record as $columnKey => $value) {
                $parsedData[$columnKey][] = $value;
            }
        }

        return $parsedData;
    }

    public function getPdfText(QuoteFile $quoteFile)
    {
        $filePath = Storage::path($quoteFile->original_file_path);

        $document = $this->pdfParser->parseFile($filePath);
        
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
