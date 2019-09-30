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
use App\Imports \ {
    ImportedRowImport,
    CountPages
};
use App\Models \ {
    Quote\Quote,
    QuoteFile\QuoteFile
};
use App\Http\Requests \ {
    StoreQuoteFileRequest,
    HandleQuoteFileRequest
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

        ['rowsData' => $rowsData, 'handled' => $handled] = $this->handleOrRetrieve(
            $quote, $quoteFile, $separator
        );

        if($handled) {
            $this->mapColumnsToFields($quote, $quoteFile);
        };

        return $rowsData;
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
        }])->first();

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
    }

    public function handleOrRetrieve(Quote $quote, QuoteFile $quoteFile, $separator)
    {
        $handled = false;

        if($quoteFile->isHandled() && $quoteFile->isSchedule()) {
            $rowsData = $this->quoteFile->getScheduleData($quoteFile);

            return compact('rowsData', 'handled');
        }

        if(
            $quoteFile->isHandled() &&
            !($quoteFile->isCsv() && $quoteFile->isNewSeparator($separator))
        ) {
            $rowsData = $this->quoteFile->getRowsData($quoteFile);

            return compact('rowsData', 'handled');
        };

        $quoteFile->quote()->associate($quote)->save();

        $rowsData = $this->routeParser($quoteFile);
        $handled = true;

        /**
         * Remove all [Distributor Price Lists|Payment Schedules] from the Quote before handling excepting new one
         */
        $this->quoteFile->deleteExcept($quoteFile);

        return compact('rowsData', 'handled');
    }

    public function routeParser(QuoteFile $quoteFile)
    {
        $fileFormat = $quoteFile->format->extension;

        switch ($fileFormat) {
            case 'pdf':
                return $this->handlePdf($quoteFile);
                break;
            case 'csv':
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
            $page = collect($rawData)->last()['page'];

            $parsedData = $this->pdfParser->parseSchedule($rawData);

            $quoteFile->setImportedPage($page);

            return $this->quoteFile->createScheduleData(
                $quoteFile,
                $parsedData
            );
        }

        $parsedData = $this->pdfParser->parse($rawData);

        return $this->quoteFile->createRowsData(
            $quoteFile,
            $parsedData
        );
    }

    public function handleExcel(QuoteFile $quoteFile)
    {
        if($quoteFile->isCsv() && request()->has('data_select_separator_id')) {
            $dataSelectSeparator = $this->dataSelectSeparator->find(request()->data_select_separator_id);
            $quoteFile->dataSelectSeparator()->associate($dataSelectSeparator)->save();
        }

        $this->importExcel($quoteFile);

        return $this->quoteFile->getRowsData($quoteFile);
    }

    public function handleWord(QuoteFile $quoteFile)
    {
        $separator = $this->dataSelectSeparator->findByName($this->defaultSeparator);
        $quoteFile->dataSelectSeparator()->associate($separator)->save();

        $this->importWord($quoteFile);

        return $this->quoteFile->getRowsData($quoteFile);
    }

    public function importExcel(QuoteFile $quoteFile)
    {
        $filePath = $quoteFile->original_file_path;
        $user = $quoteFile->user;
        $columns = $this->importableColumn->allSystem();

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
