<?php

namespace App\Services;

use App\Contracts\{
    Services\ParserServiceInterface,
    Services\WordParserInterface as WordParser,
    Services\PdfParserInterface as PdfParser,
    Services\CsvParserInterface as CsvParser,
    Repositories\Quote\QuoteRepositoryInterface as QuoteRepository,
    Repositories\QuoteFile\QuoteFileRepositoryInterface as QuoteFileRepository,
    Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumn,
    Repositories\QuoteFile\FileFormatRepositoryInterface as FileFormatRepository,
    Repositories\QuoteFile\DataSelectSeparatorRepositoryInterface as DataSelectSeparatorRepository
};
use App\Models\{
    Quote\Quote,
    QuoteFile\QuoteFile,
    Quote\FieldColumn
};
use App\Http\Requests\{
    StoreQuoteFileRequest,
    HandleQuoteFileRequest
};
use App\Imports\{
    ImportCsv,
    ImportExcel,
    ImportExcelSchedule,
    CountPages
};
use Excel, Storage, File, Setting, DB;

class ParserService implements ParserServiceInterface
{
    protected $quote;

    protected $quoteFile;

    protected $importableColumn;

    protected $fileFormat;

    protected $dataSelectSeparator;

    protected $pdfParser;

    protected $wordParser;

    protected $csvParser;

    protected $defaultPage;

    protected $defaultSeparator;

    public function __construct(
        QuoteRepository $quote,
        QuoteFileRepository $quoteFile,
        ImportableColumn $importableColumn,
        FileFormatRepository $fileFormat,
        DataSelectSeparatorRepository $dataSelectSeparator,
        PdfParser $pdfParser,
        WordParser $wordParser,
        CsvParser $csvParser
    ) {
        $this->quote = $quote;
        $this->quoteFile = $quoteFile;
        $this->importableColumn = $importableColumn;
        $this->fileFormat = $fileFormat;
        $this->dataSelectSeparator = $dataSelectSeparator;
        $this->pdfParser = $pdfParser;
        $this->wordParser = $wordParser;
        $this->csvParser = $csvParser;
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

        $attributes = compact('format', 'pages', 'original_file_path');

        switch ($format->extension) {
            case 'pdf':
                $rawData = $this->pdfParser->getText($original_file_path);
                $attributes = array_merge($attributes, compact('rawData'));
                break;
            case 'docx':
            case 'doc':
                $rawData = $this->wordParser->getText($original_file_path);
                $attributes = array_merge($attributes, compact('rawData'));
                break;
            case 'csv':
                $attributes = array_merge($attributes, $this->guessDelimiter($original_file_path));
                break;
        }

        return array_merge($request->validated(), $attributes);
    }

    public function handle(HandleQuoteFileRequest $request)
    {
        $quote = $this->quote->find($request->quote_id);

        error_abort_if(!$quote->quoteTemplate()->exists(), QNT_01, 'QNT_01', 422);

        $quoteFile = $this->quoteFile->find($request->quote_file_id);

        $quoteFile->setImportedPage($request->page);

        $separator = $request->data_select_separator_id;

        $this->handleOrRetrieve($quote, $quoteFile, $separator);

        $quoteFile->throwExceptionIfExists();

        if ($quoteFile->isPrice() && $quoteFile->isNotAutomapped() && $quoteFile->processing_percentage > 1) {
            $this->mapColumnsToFields($quote, $quoteFile);
        }

        return $quoteFile->processing_state;
    }

    public function mapColumnsToFields(Quote $quote, QuoteFile $quoteFile)
    {
        /**
         * Detach existing relations
         */
        $quote->usingVersion->detachColumnsFields();

        $templateFields = $quote->quoteTemplate->templateFields;

        $rowData = $quoteFile->rowsData()->with(['columnsData' => function ($query) {
            return $query->whereHas('importableColumn');
        }])->processed()->first();

        if (blank($rowData)) {
            return;
        }

        $defaultAttributes = app(FieldColumn::class)->defaultAttributesToArray();

        $rowData->columnsData->each(function ($column) use ($quote, $templateFields, $defaultAttributes) {
            $templateField = $templateFields->where('name', $column->importableColumn->name)->first();

            if (!isset($templateField)) {
                return true;
            }

            $quote->usingVersion->attachColumnToField($templateField, $column->importableColumn, $defaultAttributes);
        });

        $quote->usingVersion->forgetCachedMappingReview();

        return $quoteFile->markAsAutomapped();
    }

    public function handleOrRetrieve(Quote $quote, QuoteFile $quoteFile, $separator)
    {
        if (($quoteFile->isHandled() && $quoteFile->isPrice()) || ($quoteFile->isHandled() && $quoteFile->isSchedule() && !$quoteFile->isNewPage(request()->page))) {
            return false;
        }

        if (($quoteFile->isHandled() && $quoteFile->isPrice()) && !($quoteFile->isCsv() && $quoteFile->isNewSeparator($separator))) {
            return false;
        };

        $quoteFile->clearException();

        $quoteFile->quote()->associate(
            $this->quote->createNewVersionIfNonCreator($quote)
        )->save();

        $this->routeParser($quoteFile);
        $this->quoteFile->deleteExcept($quoteFile);

        /**
         * Clear Cache Mapping Review Data After Handling
         */
        if ($quoteFile->isPrice()) {
            $quote->usingVersion->forgetCachedMappingReview();
            $quote->usingVersion->resetGroupDescription();
        }

        return true;
    }

    public function routeParser(QuoteFile $quoteFile)
    {
        DB::transaction(function () use ($quoteFile) {
            switch ($quoteFile->format->extension) {
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
                    error_abort(QFTNS_01, 'QFTNS_01',  422);
                    break;
            }
        });
    }

    public function handlePdf(QuoteFile $quoteFile)
    {
        $rawData = $this->quoteFile->getRawData($quoteFile)->toArray();

        if ($quoteFile->isSchedule()) {
            $pageData = collect($rawData)->firstWhere('page', $quoteFile->imported_page);

            $parsedData = $this->pdfParser->parseSchedule($pageData);

            $this->quoteFile->createScheduleData(
                $quoteFile,
                $parsedData
            );

            return $quoteFile->markAsHandled();
        }

        $parsedData = $this->pdfParser->parse($rawData);

        $this->quoteFile->createRowsData(
            $quoteFile,
            $parsedData
        );

        return $quoteFile->markAsHandled();
    }

    public function handleExcel(QuoteFile $quoteFile)
    {
        if ($quoteFile->isSchedule()) {
            return $this->importExcelSchedule($quoteFile);
        }

        return $this->importExcel($quoteFile);
    }

    public function handleCsv(QuoteFile $quoteFile)
    {
        if (request()->has('data_select_separator_id')) {
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

    public function importExcelSchedule(QuoteFile $quoteFile)
    {
        $filePath = $quoteFile->original_file_path;

        $quoteFile->scheduleData()->forceDelete();

        (new ImportExcelSchedule($quoteFile))->import($filePath);

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

    public function countPages(string $path, bool $storage = true)
    {
        $format = $this->determineFileFormat($path, $storage);

        switch ($format->extension) {
            case 'pdf':
                return $this->pdfParser->countPages($path, $storage);
                break;
            case 'xlsx':
            case 'xls':
                return $this->countExcelPages($path, $storage);
            default:
                return 1;
                break;
        }
    }

    public function countExcelPages(string $path, bool $storage = true)
    {
        $filePath = $storage ? Storage::path($path) : $path;

        $import = new CountPages;

        Excel::import($import, $filePath);

        $sheetCount = $import->getSheetCount();

        if ($sheetCount === 0) {
            throw new \ErrorException(QFNR_01);
        }

        return $import->getSheetCount();
    }

    public function determineFileFormat(string $path, bool $storage = true)
    {
        $file = $storage ? Storage::path($path) : $path;

        $extensions = collect(File::extension($file));

        if ($extensions->first() === 'txt') {
            $extensions->push('csv');
        }

        $format = $this->fileFormat->whereInExtension($extensions->toArray());

        return $format;
    }

    protected function guessDelimiter(string $filepath): array
    {
        $delimiter = $this->csvParser->guessDelimiter(Storage::path($filepath));

        $data_select_separator_id = $this->dataSelectSeparator->findByName($delimiter)->id;

        return compact('data_select_separator_id');
    }
}
