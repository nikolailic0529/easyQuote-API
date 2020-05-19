<?php

namespace App\Services;

use App\Contracts\{
    Services\ParserServiceInterface,
    Services\WordParserInterface as WordParser,
    Services\PdfParserInterface as PdfParser,
    Services\CsvParserInterface as CsvParser,
    Repositories\Quote\QuoteRepositoryInterface as QuoteState,
    Repositories\QuoteFile\QuoteFileRepositoryInterface as QuoteFileRepository,
    Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumn,
    Repositories\QuoteFile\FileFormatRepositoryInterface as FileFormatRepository,
    Repositories\QuoteFile\DataSelectSeparatorRepositoryInterface as DataSelectSeparatorRepository,
    Repositories\QuoteTemplate\TemplateFieldRepositoryInterface as TemplateFields
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
use App\Jobs\{
    MigrateQuoteAssets,
    RetrievePriceAttributes,
};
use App\Models\QuoteFile\QuoteFileFormat;
use Illuminate\Pipeline\Pipeline;
use Excel, Storage, File, Setting, DB;

class ParserService implements ParserServiceInterface
{
    protected QuoteState $quote;

    protected QuoteFileRepository $quoteFile;

    protected ImportableColumn $importableColumn;

    protected FileFormatRepository $fileFormat;

    protected DataSelectSeparatorRepository $dataSelectSeparator;

    protected PdfParser $pdfParser;

    protected WordParser $wordParser;

    protected CsvParser $csvParser;

    public function __construct(
        QuoteState $quote,
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
    }

    public function preHandle(StoreQuoteFileRequest $request): array
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

        $quoteFile = $this->quoteFile->find($request->quote_file_id);

        $this->handleOrRetrieve($quote, $quoteFile);

        $quoteFile->throwExceptionIfExists();

        if ($quoteFile->isPrice() && $quoteFile->isNotAutomapped()) {
            $this->mapColumnsToFields($quote, $quoteFile);
            dispatch(new RetrievePriceAttributes($quote->usingVersion));
        }

        return $quoteFile->processing_state;
    }

    public function mapColumnsToFields(Quote $quote, QuoteFile $quoteFile): void
    {
        $templateFields = app(TemplateFields::class)->allSystem();
        $fieldsNames = $templateFields->pluck('name')->toArray();

        $row = $quoteFile->rowsData()->first();

        $columns = optional($row)->columns_data;

        if (blank($columns)) {
            $quote->usingVersion->detachColumnsFields();
            $quote->usingVersion->forgetCachedMappingReview();
            $quoteFile->markAsAutomapped();
            return;
        }

        $defaultAttributes = FieldColumn::defaultAttributesToArray();

        $importableColumns = $this->importableColumn->findByIds($columns->pluck('importable_column_id'))->pluck('id', 'name');

        $map = $templateFields->pluck('id', 'name')
            ->mergeRecursive($importableColumns)
            ->filter(fn ($map) => is_array($map) && count($map) === 2)
            ->mapWithKeys(fn ($map, $key) => [
                $map[0] => ['importable_column_id' => $map[1]] + $defaultAttributes
            ]);

        $quote->usingVersion->templateFields()->sync($map->toArray());

        $quote->usingVersion->forgetCachedMappingReview();

        $quoteFile->markAsAutomapped();
    }

    public function routeParser(QuoteFile $quoteFile): void
    {
        switch ($quoteFile->format->extension) {
            case 'pdf':
                $this->handlePdf($quoteFile);
                break;
            case 'csv':
                $this->handleCsv($quoteFile);
                break;
            case 'xlsx':
            case 'xls':
                $this->handleExcel($quoteFile);
                break;
            case 'doc':
            case 'docx':
                $this->handleWord($quoteFile);
                break;
            default:
                error_abort(QFTNS_01, 'QFTNS_01',  422);
                break;
        }
    }

    public function countPages(string $path, bool $storage = true): int
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

    public function determineFileFormat(string $path, bool $storage = true): QuoteFileFormat
    {
        $file = $storage ? Storage::path($path) : $path;

        $extensions = collect(File::extension($file));

        if ($extensions->first() === 'txt') {
            $extensions->push('csv');
        }

        $format = $this->fileFormat->whereInExtension($extensions->toArray());

        return $format;
    }

    protected function handlePdf(QuoteFile $quoteFile): void
    {
        DB::transaction(function () use ($quoteFile) {
            $rawData = $this->quoteFile->getRawData($quoteFile)->toArray();

            if ($quoteFile->isSchedule()) {
                $pageData = collect($rawData)->firstWhere('page', $quoteFile->imported_page);

                $parsedData = $this->pdfParser->parseSchedule($pageData);

                $this->quoteFile->createScheduleData($quoteFile, $parsedData);

                $quoteFile->markAsHandled();
                return;
            }

            $parsedData = $this->pdfParser->parse($rawData);

            $this->quoteFile->createRowsData($quoteFile, $parsedData['pages']);

            tap($quoteFile)->storeMetaAttributes($parsedData['attributes'])->markAsHandled();
        }, 3);
    }

    protected function handleExcel(QuoteFile $quoteFile): void
    {
        $method = $quoteFile->isSchedule() ? 'importExcelSchedule' : 'importExcel';

        DB::transaction(fn () => $this->{$method}($quoteFile), 3);
    }

    protected function handleCsv(QuoteFile $quoteFile): void
    {
        if (request()->has('data_select_separator_id')) {
            $quoteFile->dataSelectSeparator()->associate(request()->data_select_separator_id)->save();
        }

        DB::transaction(fn () => $this->importCsv($quoteFile), 3);
    }

    protected function handleWord(QuoteFile $quoteFile): void
    {
        $separator = $this->dataSelectSeparator->findByName(Setting::get('parser.default_separator'));
        $quoteFile->dataSelectSeparator()->associate($separator)->save();

        DB::transaction(fn () => $this->importWord($quoteFile), 3);
    }

    protected function importExcel(QuoteFile $quoteFile): void
    {
        $filePath = $quoteFile->original_file_path;

        $quoteFile->rowsData()->forceDelete();

        (new ImportExcel($quoteFile))->import($filePath);

        $quoteFile->markAsHandled();
    }

    protected function importExcelSchedule(QuoteFile $quoteFile): void
    {
        $filePath = $quoteFile->original_file_path;

        $quoteFile->scheduleData()->forceDelete();

        (new ImportExcelSchedule($quoteFile))->import($filePath);

        $quoteFile->markAsHandled();
    }

    protected function importCsv(QuoteFile $quoteFile): void
    {
        $quoteFile->rowsData()->forceDelete();

        (new ImportCsv($quoteFile))->import();

        $quoteFile->markAsHandled();
    }

    protected function importWord(QuoteFile $quoteFile): void
    {
        $rawData = $this->quoteFile->getRawData($quoteFile);

        $quoteFile->rowsData()->forceDelete();

        $rawData->each(fn ($file) => (new ImportCsv($quoteFile, $file->file_path))->import());

        $quoteFile->markAsHandled();
    }

    protected function countExcelPages(string $path, bool $storage = true): int
    {
        $filePath = $storage ? Storage::path($path) : $path;

        $import = new CountPages;

        Excel::import($import, $filePath);

        $sheetCount = $import->getSheetCount();

        if ($sheetCount === 0) {
            error_abort(QFNR_01, 'QFNR_01', 422);
        }

        return $import->getSheetCount();
    }

    protected function handleOrRetrieve(Quote $quote, QuoteFile $quoteFile): bool
    {
        app(Pipeline::class)
            ->send($quoteFile)
            ->through(
                \App\Services\HandledCases\HasException::class,
                \App\Services\HandledCases\HasNotBeenProcessed::class,
                \App\Services\HandledCases\RequestedNewPageForPrice::class,
                \App\Services\HandledCases\RequestedNewPageForSchedule::class,
                \App\Services\HandledCases\RequestedNewSeparatorForCsv::class
            )
            ->thenReturn();

        if ($quoteFile->shouldNotBeHandled) {
            return false;
        }

        $version = $this->quote->createNewVersionIfNonCreator($quote);

        $quoteFile->setImportedPage(request()->page);
        $quoteFile->clearException();

        $quoteFile->quote()->associate($version)->save();

        $this->routeParser($quoteFile);
        $this->quoteFile->deleteExcept($quoteFile);

        /**
         * Clear Cache Mapping Review Data After Processing.
         */
        if ($quoteFile->isPrice()) {
            $version->forgetCachedMappingReview();
            $version->resetGroupDescription();
        }

        return true;
    }

    protected function guessDelimiter(string $filepath): array
    {
        $delimiter = $this->csvParser->guessDelimiter(Storage::path($filepath));

        $data_select_separator_id = $this->dataSelectSeparator->findByName($delimiter)->id;

        return compact('data_select_separator_id');
    }
}
