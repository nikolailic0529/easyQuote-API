<?php namespace App\Services;

use App\Contracts \ {
    Services\ParserServiceInterface,
    Services\WordParserInterface as WordParser,
    Services\PdfParserInterface as PdfParser,
    Repositories\Quote\QuoteRepositoryInterface as QuoteRepository,
    Repositories\QuoteFile\QuoteFileRepositoryInterface as QuoteFileRepository,
    Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumn,
    Repositories\QuoteFile\FileFormatRepositoryInterface as FileFormatRepository,
    Repositories\QuoteFile\DataSelectSeparatorRepositoryInterface as DataSelectSeparatorRepository
};
use App\Imports\CountPages;
use App\Models \ {
    Quote\Quote,
    QuoteFile\QuoteFile
};
use App\Http\Requests \ {
    StoreQuoteFileRequest,
    HandleQuoteFileRequest
};
use App\Imports \ {
    ImportCsv,
    ImportExcel
};
use Excel, Storage, File, Setting;

class ParserService implements ParserServiceInterface
{
    protected $quote;

    protected $quoteFile;

    protected $importableColumn;

    protected $fileFormat;

    protected $dataSelectSeparator;

    protected $pdfParser;

    protected $wordParser;

    protected $defaultPage;

    protected $defaultSeparator;

    public function __construct(
        QuoteRepository $quote,
        QuoteFileRepository $quoteFile,
        ImportableColumn $importableColumn,
        FileFormatRepository $fileFormat,
        DataSelectSeparatorRepository $dataSelectSeparator,
        PdfParser $pdfParser,
        WordParser $wordParser
    ) {
        $this->quote = $quote;
        $this->quoteFile = $quoteFile;
        $this->importableColumn = $importableColumn;
        $this->fileFormat = $fileFormat;
        $this->dataSelectSeparator = $dataSelectSeparator;
        $this->pdfParser = $pdfParser;
        $this->wordParser = $wordParser;
        $this->defaultPage = Setting::get('parser.default_page');
        $this->defaultSeparator = Setting::get('parser.default_separator');
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
                $rawData = $this->pdfParser->getText($original_file_path);
                $mergeData = array_merge($mergeData, compact('rawData'));
                break;
            case 'docx':
            case 'doc':
                $rawData = $this->wordParser->getText($original_file_path);
                $mergeData = array_merge($mergeData, compact('rawData'));
                break;
        }

        return $request->merge($mergeData);
    }

    public function handle(HandleQuoteFileRequest $request)
    {
        $quote = $this->quote->find($request->quote_id);

        if(!$quote->quoteTemplate()->exists()) {
            throw new \ErrorException(__('parser.quote_has_not_template_exception'));
        };

        $quoteFile = $this->quoteFile->find($request->quote_file_id);

        $quoteFile->setImportedPage($request->page);

        $separator = $request->data_select_separator_id;

        $handled = $this->handleOrRetrieve($quote, $quoteFile, $separator);

        $quoteFile->throwExceptionIfExists();

        if(!$quoteFile->isSchedule()) {
            $processed = $quoteFile->processing_percentage;
            $status = $quoteFile->processing_status;

            if($processed > 1 && $quoteFile->isNotAutomapped()) {
                $this->mapColumnsToFields($quote, $quoteFile);
            }

        } else {
            $status = 'completted';
            $processed = 100;
        }

        return compact('status', 'processed');
    }

    public function mapColumnsToFields(Quote $quote, QuoteFile $quoteFile)
    {
        /**
         * Detach existing relations
         */
        $quote->detachColumnsFields();

        $templateFields = $quote->quoteTemplate->templateFields;

        $rowData = $quoteFile->rowsData()->with(['columnsData' => function ($query) {
            return $query->whereHas('importableColumn');
        }])->processed()->first();

        if(!isset($rowData)) {
            return;
        }

        $columnsData = $rowData->columnsData;
        $columnsData->each(function ($columnData) use ($quote, $templateFields) {
            $templateField = $templateFields->where('name', $columnData->importableColumn->name)->first();

            if(!isset($templateField)) {
                return true;
            }

            $quote->attachColumnToField($templateField, $columnData->importableColumn);
        });

        return $quoteFile->markAsAutomapped();
    }

    public function handleOrRetrieve(Quote $quote, QuoteFile $quoteFile, $separator)
    {
        if(($quoteFile->isHandled() && $quoteFile->isPrice()) || ($quoteFile->isHandled() && $quoteFile->isSchedule() && !$quoteFile->isNewPage(request()->page))) {
            return false;
        }

        if(($quoteFile->isHandled() && $quoteFile->isPrice()) && !($quoteFile->isCsv() && $quoteFile->isNewSeparator($separator))) {
            return false;
        };

        $quoteFile->clearException();
        $quoteFile->quote()->associate($quote)->save();
        $this->routeParser($quoteFile);
        $this->quoteFile->deleteExcept($quoteFile);

        /**
         * Re-Cache Mapping Review Data After Handling
         */
        $this->quote->mappingReviewData($quote, true);

        return true;
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
                break;
            case 'xlsx':
            case 'xls':
                return $this->handleExcel($quoteFile);
                break;
            case 'doc':
            case 'docx':
                return $this->handleWord($quoteFile);
                break;
            default:
                return response()->json([
                    'message' => __('parser.no_handleable_file')
                ], 415);
                break;
        }
    }

    public function handlePdf(QuoteFile $quoteFile)
    {
        $rawData = $this->quoteFile->getRawData($quoteFile)->toArray();

        if($quoteFile->isSchedule()) {
            $page = request()->has('page') ? request()->page : collect($rawData)->last()['page'];

            $pageData = collect($rawData)->firstWhere('page', $page);

            $parsedData = $this->pdfParser->parseSchedule($pageData);

            $this->quoteFile->createScheduleData(
                $quoteFile, $parsedData
            );

            return $quoteFile->markAsHandled();
        }

        $parsedData = $this->pdfParser->parse($rawData);

        $this->quoteFile->createRowsData(
            $quoteFile, $parsedData
        );

        return $quoteFile->markAsHandled();
    }

    public function handleExcel(QuoteFile $quoteFile)
    {
        $this->importExcel($quoteFile);
    }

    public function handleCsv(QuoteFile $quoteFile)
    {
        if(request()->has('data_select_separator_id')) {
            $dataSelectSeparator = $this->dataSelectSeparator->find(request()->data_select_separator_id);
            $quoteFile->dataSelectSeparator()->associate($dataSelectSeparator)->save();
        }

        $this->importCsv($quoteFile);
    }

    public function handleWord(QuoteFile $quoteFile)
    {
        $separator = $this->dataSelectSeparator->findByName($this->defaultSeparator);
        $quoteFile->dataSelectSeparator()->associate($separator)->save();

        $this->importWord($quoteFile);
    }

    public function importExcel(QuoteFile $quoteFile)
    {
        $filePath = $quoteFile->original_file_path;

        $quoteFile->rowsData()->forceDelete();

        (new ImportExcel($quoteFile))->import($filePath);

        $quoteFile->markAsHandled();
    }

    public function importCsv(QuoteFile $quoteFile)
    {
        $quoteFile->rowsData()->forceDelete();

        (new ImportCsv($quoteFile))->import();

        $quoteFile->markAsHandled();
    }

    public function importWord(QuoteFile $quoteFile)
    {
        $rawData = $this->quoteFile->getRawData($quoteFile);

        $quoteFile->rowsData()->forceDelete();

        $rawData->each(function ($file) use ($quoteFile) {
            $filePath = $file->file_path;

            (new ImportCsv($quoteFile, $filePath))->import();
        });

        $quoteFile->markAsHandled();
    }

    public function countPages(string $path)
    {
        $format = $this->determineFileFormat($path);

        switch ($format->extension) {
            case 'pdf':
                return $this->pdfParser->countPages($path);
                break;
            case 'xlsx':
            case 'xls':
                return $this->countExcelPages($path);
            default:
                return 1;
                break;
        }
    }

    public function countExcelPages(string $path)
    {
        $filePath = Storage::path($path);

        $import = new CountPages;

        Excel::import($import, $filePath);

        $sheetCount = $import->getSheetCount();

        if($sheetCount === 0) {
            throw new \ErrorException(__('parser.excel.unreadable_file_exception'));
        }

        return $import->getSheetCount();
    }

    public function determineFileFormat(string $path)
    {
        $file = Storage::path($path);

        $extensions = collect(File::extension($file));

        if($extensions->first() === 'txt') {
            $extensions->push('csv');
        }

        $format = $this->fileFormat->whereInExtension($extensions->toArray());

        return $format;
    }
}
