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
    QuoteFile,
    QuoteFileFormat,
    DataSelectSeparator
};
use App\Http\Requests \ {
    StoreQuoteFileRequest,
    HandleQuoteFileRequest
};
use Storage, File;

class ParserService implements ParserServiceInterface
{
    protected $pdfParser;

    protected $importableColumn;

    protected $defaultPage;

    public function __construct(
        QuoteFileRepository $quoteFile,
        ImportableColumn $importableColumn,
        PdfParser $pdfParser
    ) {
        $this->quoteFile = $quoteFile;
        $this->importableColumn = $importableColumn;
        $this->pdfParser = $pdfParser;
        $this->defaultPage = 2;
    }

    public function preHandle(StoreQuoteFileRequest $request)
    {
        $tempFile = $request->file('quote_file');
        
        $original_file_path = $tempFile->store(
            $request->user()->quoteFilesDirectory
        );

        $format = $this->determineFileFormat($original_file_path);

        $pages = $this->countPages($original_file_path);

        return $request->merge(compact('format', 'pages', 'original_file_path'));
    }

    public function handle(HandleQuoteFileRequest $request)
    {
        $quoteFile = $this->quoteFile->get($request->quote_file_id);

        $page = $request->page ?: $this->defaultPage;

        if($quoteFile->isHandled()) {
            return $this->quoteFile->getRowsData($quoteFile, $page);
        };

        return $this->routeParser($quoteFile, $page);
    }

    public function routeParser(QuoteFile $quoteFile, Int $page)
    {
        $fileFormat = $quoteFile->format->extension;

        switch ($fileFormat) {
            case 'pdf':
                return $this->handlePdf($quoteFile, $page);
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

    public function handlePdf(QuoteFile $quoteFile, Int $page)
    {
        $rawPages = $this->getPdfText($quoteFile);

        $this->quoteFile->createRawData(
            $quoteFile,
            $rawPages
        );

        $parsedData = $this->parsePdfText(
            $this->quoteFile->getRawData($quoteFile)
        );

        return $this->quoteFile->createRowsData(
            $quoteFile,
            $parsedData,
            $page
        );
    }

    public function handleCsv(QuoteFile $quoteFile)
    {
        $rawData = $this->getCsvText($quoteFile);

        $parsedData = $this->parseCsvText($rawData, $quoteFile);

        return $this->quoteFile->createRowsData(
            $quoteFile,
            $parsedData,
            1
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

        $dataSelectSeparator = DataSelectSeparator::whereId(request()->data_select_separator_id)->first();

        $quoteFile->dataSelectSeparator()->associate($dataSelectSeparator);

        $separator = $dataSelectSeparator->separator;

        $document->setDelimiter($separator);

        try {
            $rows = collect($document)->toArray();
            $page = 1;

            return [
                compact('page', 'rows')
            ];
        } catch (CsvParserException $exception) {
            abort(415, __('Please set the headers in the CSV file'));
        }
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

    public function parsePdfText(Array $array)
    {
        $regexpColumns = $this->importableColumn->allColumnsRegs();

        $regexp = $regexpColumns->implode('');
        $regexp = "/^{$regexp}$/mu";

        $pages = collect($array)->map(function ($pageData, $key) use ($regexp) {

            $content = $pageData['content'];
            $page = $pageData['page'];

            preg_match_all($regexp, $content, $matches, PREG_UNMATCHED_AS_NULL);

            $columnsAliases = $this->importableColumn->allColumnsAliases();
    
            $matches = collect($matches)->only(
                $columnsAliases
            )->toArray();
            
            $rows = [];
    
            foreach ($matches as $column => $values) {
                foreach ($values as $key => $value) {
                    $rows[$key][$column] = $value;
                }
            }

            return compact('page', 'rows');
        });

        return $pages->toArray();
    }

    public function countPages(String $path)
    {
        $format = $this->determineFileFormat($path);

        switch ($format->extension) {
            case 'pdf':
                return $this->countPdfPages($path);
                break;
            default:
                return 1;
                break;
        }
    }

    public function countPdfPages(String $path)
    {
        $filePath = Storage::path($path);

        $document = $this->pdfParser->parseFile($filePath);
        
        return count($document->getPages());
    }

    public function determineFileFormat(String $path)
    {
        $file = Storage::path($path);

        $extension = collect(File::extension($file));
        
        if($extension->first() === 'txt') {
            $extension->push('csv');
        }

        $format = QuoteFileFormat::whereIn('extension', $extension)->firstOrFail();

        return $format;
    }
}
