<?php namespace App\Services;

use App\Contracts \ {
    Services\ParserServiceInterface,
    Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumn,
    Repositories\QuoteFile\QuoteFileRepositoryInterface as QuoteFileRepository
};
use Smalot\PdfParser\Parser as PdfParser;
use League\Csv\Reader as CsvParser;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Imports\ImportedRowImport, Excel, Storage, File;
use App\Models\QuoteFile \ {
    QuoteFile,
    QuoteFileFormat,
    DataSelectSeparator
};
use App\Http\Requests \ {
    StoreQuoteFileRequest,
    HandleQuoteFileRequest
};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\LazyCollection;

class ParserService implements ParserServiceInterface
{
    protected $pdfParser;

    protected $importableColumn;

    protected $dataSelectSeparator;

    protected $defaultPage;

    public function __construct(
        QuoteFileRepository $quoteFile,
        ImportableColumn $importableColumn,
        DataSelectSeparator $dataSelectSeparator,
        PdfParser $pdfParser
    ) {
        $this->quoteFile = $quoteFile;
        $this->importableColumn = $importableColumn;
        $this->dataSelectSeparator = $dataSelectSeparator;
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

        $mergeData = compact('format', 'pages', 'original_file_path');

        if($format->extension === 'pdf') {
            $rawData = $this->getPdfText($original_file_path);

            $mergeData = array_merge($mergeData, compact('rawData'));
        }

        return $request->merge($mergeData);
    }

    public function handle(HandleQuoteFileRequest $request)
    {
        $quoteFile = $this->quoteFile->find($request->quote_file_id);

        $page = $request->page ?: $this->defaultPage;

        if(
            $quoteFile->isHandled() &&
            !($quoteFile->isCsv() && $quoteFile->isNewDataSelectSeparator($request->data_select_separator_id))
        ) {
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
            case 'xlsx':
                return $this->handleExcel($quoteFile, $page);
                break;
            default:
                return response()->json([
                    'message' => __('This file format is not available for handling')
                ], 415);
                break;
        }
    }

    public function handlePdf(QuoteFile $quoteFile, Int $requestedPage)
    {
        $parsedData = $this->parsePdfText(
            $this->quoteFile->getRawData($quoteFile)
        );

        return $this->quoteFile->createRowsData(
            $quoteFile,
            $parsedData,
            $requestedPage
        );
    }

    public function handleExcel(QuoteFile $quoteFile, Int $requestedPage)
    {
        if($quoteFile->isCsv() && request()->has('data_select_separator_id')) {
            $dataSelectSeparator = $this->dataSelectSeparator->whereId(request()->data_select_separator_id)->first();
            $quoteFile->dataSelectSeparator()->associate($dataSelectSeparator);
        }

        $this->importExcel($quoteFile);

        return $this->quoteFile->getRowsData($quoteFile, $requestedPage);
    }

    public function getPdfText(String $path)
    {
        $filePath = Storage::path($path);

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

        $pages = LazyCollection::make(function () use ($array) {
            foreach ($array as $page) {
                $data = [
                    'page' => $page['page'],
                    'content' => Storage::get($page['file_path'])
                ];
                
                yield $data;
            }
        });
        
        $pagesData = $pages->map(function ($page, $key) use ($regexp) {
            ['page' => $page, 'content' => $content] = $page;

            preg_match_all($regexp, $content, $matches, PREG_UNMATCHED_AS_NULL);

            $columnsAliases = $this->importableColumn->allNames();
    
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

        return $pagesData->toArray();
    }

    public function importExcel(QuoteFile $quoteFile)
    {
        $filePath = $quoteFile->original_file_path;
        $user = $quoteFile->user;
        $columns = $this->importableColumn->all();
        
        $quoteFile->columnsData()->forceDelete();
        $quoteFile->rowsData()->forceDelete();

        (new ImportedRowImport($quoteFile, $user, $columns))->import($filePath);

        $quoteFile->markAsHandled();

        return $quoteFile;
    }

    public function countPages(String $path)
    {
        $format = $this->determineFileFormat($path);

        switch ($format->extension) {
            case 'pdf':
                return $this->countPdfPages($path);
                break;
            case 'xlsx':
            case 'xls':
                return $this->countExcelPages($path);
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

    public function countExcelPages(String $path)
    {
        $filePath = Storage::path($path);

        $factory = IOFactory::load($filePath);

        return $factory->getSheetCount();
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
