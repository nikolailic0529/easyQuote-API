<?php namespace App\Services;

use App\Contracts \ {
    Services\ParserServiceInterface,
    Services\WordParserInterface as WordParser,
    Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumn,
    Repositories\QuoteFile\QuoteFileRepositoryInterface as QuoteFileRepository,
    Repositories\QuoteFile\DataSelectSeparatorRepositoryInterface as DataSelectSeparatorRepository
};
use Smalot\PdfParser\Parser as PdfParser;
use League\Csv\Reader as CsvParser;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Imports\ImportedRowImport, Excel, Storage, File;
use App\Models\QuoteFile \ {
    QuoteFile,
    QuoteFileFormat
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

    protected $wordParser;

    protected $importableColumn;

    protected $dataSelectSeparator;

    protected $defaultPage;

    protected $defaultSeparator;

    public function __construct(
        QuoteFileRepository $quoteFile,
        ImportableColumn $importableColumn,
        DataSelectSeparatorRepository $dataSelectSeparator,
        PdfParser $pdfParser,
        WordParser $wordParser
    ) {
        $this->quoteFile = $quoteFile;
        $this->importableColumn = $importableColumn;
        $this->dataSelectSeparator = $dataSelectSeparator;
        $this->pdfParser = $pdfParser;
        $this->wordParser = $wordParser;
        $this->defaultPage = 2;
        $this->defaultSeparator = "\t";
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

        switch ($format->extension) {
            case 'pdf':
                $rawData = $this->getPdfText($original_file_path);
                $mergeData = array_merge($mergeData, compact('rawData'));
                break;
            case 'docx':
            case 'doc':
                $rawData = $this->getWordText($original_file_path);
                $mergeData = array_merge($mergeData, compact('rawData'));
                break;
        }

        return $request->merge($mergeData);
    }

    public function getWordText(string $filePath)
    {
        $columns = $this->importableColumn->all();

        $rows = $this->wordParser->load($filePath)->getTables()->getRows($columns);

        if(empty($rows)) {
            throw new \ErrorException('Uploaded file has not any required columns');
        }

        $page = 1;
        $content = null;

        $rowsLines = [];
        $rowsLines[] = implode($this->defaultSeparator, $rows['header']);

        foreach ($rows['rows'] as $row) {
            $rowsLines[] = implode($this->defaultSeparator, $row['cells']);
        }

        $content = implode(PHP_EOL, $rowsLines);

        $rawPages = [compact('page', 'content')];

        return $rawPages;
    }

    public function handle(HandleQuoteFileRequest $request)
    {
        $quoteFile = $this->quoteFile->find($request->quote_file_id);

        $page = $request->page ?: $this->defaultPage;

        if($request->has('data_select_separator')) {
            $separator = $request->data_select_separator_id;
        }

        if(
            $quoteFile->isHandled() &&
            !($quoteFile->isCsv() && $quoteFile->isNewSeparator($separator))
        ) {
            return $this->quoteFile->getRowsData($quoteFile, $page);
        };

        return $this->routeParser($quoteFile, $page);
    }

    public function routeParser(QuoteFile $quoteFile, int $page)
    {
        $fileFormat = $quoteFile->format->extension;

        switch ($fileFormat) {
            case 'pdf':
                return $this->handlePdf($quoteFile, $page);
                break;
            case 'csv':
            case 'xlsx':
            case 'xls':
                return $this->handleExcel($quoteFile, $page);
                break;
            case 'doc':
            case 'docx':
                return $this->handleWord($quoteFile, $page);
                break;
            default:
                return response()->json([
                    'message' => __('This file format is not available for handling')
                ], 415);
                break;
        }
    }

    public function handlePdf(QuoteFile $quoteFile, int $requestedPage)
    {
        $parsedData = $this->parsePdfText(
            $this->quoteFile->getRawData($quoteFile)->toArray()
        );

        return $this->quoteFile->createRowsData(
            $quoteFile,
            $parsedData,
            $requestedPage
        );
    }

    public function handleExcel(QuoteFile $quoteFile, int $requestedPage)
    {
        if($quoteFile->isCsv() && request()->has('data_select_separator_id')) {
            $dataSelectSeparator = $this->dataSelectSeparator->find(request()->data_select_separator_id);
            $quoteFile->dataSelectSeparator()->associate($dataSelectSeparator);
        }

        $this->importExcel($quoteFile);

        return $this->quoteFile->getRowsData($quoteFile, $requestedPage);
    }

    public function handleWord(QuoteFile $quoteFile, int $requestedPage)
    {
        $separator = $this->dataSelectSeparator->findBySeparator($this->defaultSeparator);
        $quoteFile->dataSelectSeparator()->associate($separator);

        $this->importWord($quoteFile);

        return $this->quoteFile->getRowsData($quoteFile, $requestedPage);
    }

    public function getPdfText(string $path)
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

    public function importWord(QuoteFile $quoteFile)
    {
        $rawData = $this->quoteFile->getRawData($quoteFile);
        
        $quoteFile->columnsData()->forceDelete();
        $quoteFile->rowsData()->forceDelete();

        $user = $quoteFile->user;
        $columns = $this->importableColumn->all();
        
        $rawData->each(function ($file) use ($quoteFile, $user, $columns) {
            $filePath = $file->file_path;
            (new ImportedRowImport($quoteFile, $user, $columns))->import($filePath);
        });

        $quoteFile->markAsHandled();

        return $quoteFile;
    }

    public function countPages(string $path)
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

    public function countPdfPages(string $path)
    {
        $filePath = Storage::path($path);

        $document = $this->pdfParser->parseFile($filePath);
        
        return count($document->getPages());
    }

    public function countExcelPages(string $path)
    {
        $filePath = Storage::path($path);

        $factory = IOFactory::load($filePath);

        return $factory->getSheetCount();
    }

    public function determineFileFormat(string $path)
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
