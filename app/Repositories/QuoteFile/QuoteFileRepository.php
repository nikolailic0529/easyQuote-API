<?php

namespace App\Repositories\QuoteFile;

use App\Contracts\Repositories\QuoteFile\{
    QuoteFileRepositoryInterface,
    ImportableColumnRepositoryInterface as ImportableColumnRepository,
    FileFormatRepositoryInterface as FileFormatRepository
};
use App\Models\{
    Quote\Quote,
    QuoteFile\QuoteFile,
    QuoteFile\ImportedColumn,
    QuoteFile\DataSelectSeparator,
    QuoteFile\ImportedRow
};
use Illuminate\Http\UploadedFile;
use ErrorException;
use Storage, Str, File, DB;

class QuoteFileRepository implements QuoteFileRepositoryInterface
{
    protected $quoteFile;

    protected $fileFormat;

    protected $dataSelectSeparator;

    protected $importableColumn;

    protected $systemImportableColumns;

    public function __construct(
        QuoteFile $quoteFile,
        FileFormatRepository $fileFormat,
        DataSelectSeparator $dataSelectSeparator,
        ImportableColumnRepository $importableColumn
    ) {
        $this->quoteFile = $quoteFile;
        $this->fileFormat = $fileFormat;
        $this->dataSelectSeparator = $dataSelectSeparator;
        $this->importableColumn = $importableColumn;
        $this->systemImportableColumns = $importableColumn->allSystem();
    }

    public function all()
    {
        return $this->quoteFile->ordered()->get();
    }

    public function make(array $array)
    {
        return $this->quoteFile->make($array);
    }

    public function create(array $attributes)
    {
        $clientFileName = $attributes['quote_file']->getClientOriginalName();
        $fileName = Str::limit(File::name($clientFileName), 50, '');
        $extension = File::extension($clientFileName);
        $original_file_name = "{$fileName}.{$extension}";

        $quoteFile = request()->user()->quoteFiles()->make(
            array_merge($attributes, compact('original_file_name'))
        );

        if (isset($attributes['data_select_separator_id']) && isset($attributes['format']) && $attributes['format']->extension === 'csv') {
            $separator = $this->dataSelectSeparator->whereId($attributes['data_select_separator_id'])->first();

            $quoteFile->dataSelectSeparator()->associate($separator);
        }

        if (isset($attributes['format'])) {
            $quoteFile->format()->associate($attributes['format']);
        }

        $quoteFile->markAsDrafted();

        if (($quoteFile->isPdf() || $quoteFile->isWord()) && isset($attributes['rawData'])) {
            $this->createRawData($quoteFile, $attributes['rawData']);
        }

        return $quoteFile->makeHidden('user');
    }

    public function createPdf(Quote $quote, array $attributes)
    {
        if (!isset($attributes['original_file_path']) || !isset($attributes['filename'])) {
            return null;
        }

        $original_file_path = $attributes['original_file_path'];
        $quote_file = new UploadedFile(Storage::path($attributes['original_file_path']), $attributes['filename']);
        $format = $this->fileFormat->whereInExtension(['pdf']);
        $quote_id = $quote->id;
        $file_type = 'Generated PDF';

        $quote->generatedPdf()->delete();
        return $this->create(compact('quote_file', 'format', 'file_type', 'original_file_path', 'quote_id'));
    }

    public function createRawData(QuoteFile $quoteFile, array $array)
    {
        $user = $quoteFile->user;
        $rawFilesDirectory = "{$user->quoteFilesDirectory}/raw";

        $rawData = collect($array)->map(function ($data) use ($quoteFile, $user, $rawFilesDirectory) {
            $filePath = "{$rawFilesDirectory}/{$quoteFile->id}@{$data['page']}.txt";

            Storage::put($filePath, $data['content']);

            $importedRawData = $quoteFile->importedRawData()->make([
                'page' => $data['page'],
                'file_path' => $filePath
            ]);

            $importedRawData->user()->associate($user);

            $importedRawData->save();

            return $importedRawData;
        });

        return $rawData;
    }

    public function getRawData(QuoteFile $quoteFile)
    {
        return $quoteFile->importedRawData()->get();
    }

    public function createRowsData(QuoteFile $quoteFile, array $array)
    {
        /**
         * Delete early imported data
         */
        $quoteFile->rowsData()->forceDelete();

        $this->createImportedPages($array, $quoteFile);
    }

    public function createScheduleData(QuoteFile $quoteFile, array $value)
    {
        $user = request()->user();

        /**
         * Delete early imported payment schedule data
         */
        $quoteFile->scheduleData()->forceDelete();

        $scheduleData = $quoteFile->scheduleData()->make(compact('value'));
        $scheduleData->user()->associate($user);
        $scheduleData->save();

        $quoteFile->markAsHandled();

        return $scheduleData;
    }

    protected function createImportedPages(array $array, QuoteFile $quoteFile)
    {
        $array = collect($array)->filter(function ($item) {
            return isset($item['page']) && isset($item['rows']);
        });

        $importedRows = [];
        foreach ($array as $pageData) {
            ['page' => $page, 'rows' => $rows] = $pageData;
            $pageRows = $this->createImportedRows($rows, $quoteFile, $page);
            array_push($importedRows, ...$pageRows);
        }

        if (count($importedRows) < 1) {
            $quoteFile->setException(__('parser.no_rows_exception'));
            $quoteFile->markAsUnHandled();
            throw new ErrorException(__('parser.no_rows_exception'));
        }

        $quoteFile->rowsData()->saveMany($importedRows);
    }

    protected function createImportedRows(array $rows, QuoteFile $quoteFile, int $page)
    {
        $quote_file_id = $quoteFile->id;
        $user_id = $quoteFile->user->id;
        $attributes = compact('quote_file_id', 'user_id', 'page');

        return collect($rows)
            ->map(function ($row) use ($quoteFile, $attributes) {
                $importedRow = ImportedRow::make($attributes);
                $this->createRowData($row, $quoteFile, $importedRow);
                return $importedRow;
            });
    }

    protected function createRowData(array $row, QuoteFile $quoteFile, ImportedRow $importedRow, $formatHeader = true)
    {
        $user = $quoteFile->user;

        $importedRow->columnsDataToCreate = collect($row)->map(function ($value, $columnName) use ($quoteFile, $user, $importedRow, $formatHeader) {
            $importable_column_id = $this->systemImportableColumns->firstWhere('name', $columnName)->id ?? null;
            $header = Str::header($columnName, __('parser.unknown_column_header'), $formatHeader);

            $columnDataItem = ImportedColumn::make(compact('value', 'header', 'importable_column_id'));
            return $columnDataItem;
        });

        return $importedRow;
    }

    public function getRowsData(QuoteFile $quoteFile)
    {
        return $quoteFile->rowsData()->with('columnsData')->where('page', '>=', $quoteFile->imported_page)->get();
    }

    public function getScheduleData(QuoteFile $quoteFile)
    {
        return $quoteFile->scheduleData;
    }

    public function find(String $id)
    {
        return $this->quoteFile->query()->whereId($id)->firstOrFail();
    }

    public function exists(String $id)
    {
        return $this->quoteFile->whereId($id)->exists();
    }

    public function deletePriceListsExcept(QuoteFile $quoteFile)
    {
        $exceptedQuoteFileId = $quoteFile->id;
        $quote = $quoteFile->quote;

        $priceLists = $quote->quoteFiles()
            ->priceLists()->whereKeyNot($exceptedQuoteFileId)->get();

        $priceLists->each(function ($price) {
            $price->rowsData()->delete();
        });

        return $priceLists->each->delete();
    }

    public function deletePaymentSchedulesExcept(QuoteFile $quoteFile)
    {
        $exceptedQuoteFileId = $quoteFile->id;
        $quote = $quoteFile->quote;

        $paymentSchedules = $quote->quoteFiles()
            ->paymentSchedules()->whereKeyNot($exceptedQuoteFileId)->get();

        $paymentSchedules->each(function ($schedule) {
            $schedule->scheduleData()->delete();
        });

        return $paymentSchedules->each->delete();
    }

    public function deleteExcept(QuoteFile $quoteFile)
    {
        if ($quoteFile->isSchedule()) {
            return $this->deletePaymentSchedulesExcept($quoteFile);
        }

        return $this->deletePriceListsExcept($quoteFile);
    }

    public function replicatePriceList(QuoteFile $quoteFile)
    {
        if ($quoteFile->isSchedule()) {
            return $quoteFile;
        }

        $quote_file_id = $quoteFile->id;
        $user_id = $quoteFile->user_id;
        $quoteFileCopy = $quoteFile->replicate();
        $quoteFileCopy->saveOrFail();
        $new_quote_file_id = $quoteFileCopy->id;

        /**
         * Generating new Ids for Imported Columns and Rows in the temporary table
         */
        DB::select(
            'create temporary table `new_imported_columns`
            select * from `imported_columns`
            join (select uuid() as new_imported_row_id, imported_row_id as temp_imported_row_id
                from `imported_columns`
                group by imported_row_id) as temp_imported_columns
                on temp_imported_columns.temp_imported_row_id = `imported_columns`.imported_row_id
            join (select id as row_id, quote_file_id, page, is_selected, processed_at from imported_rows) as imported_rows
                on imported_rows.row_id = `imported_columns`.imported_row_id
                where imported_rows.quote_file_id = :quote_file_id',
            compact('quote_file_id')
        );

        /**
         * Inserting Imported Rows with new Ids
         */
        DB::insert(
            'insert into `imported_rows` (id, user_id, quote_file_id, page, is_selected, processed_at)
            select new_imported_row_id, :user_id, :new_quote_file_id, page, is_selected, processed_at from new_imported_columns group by new_imported_row_id',
            compact('user_id', 'new_quote_file_id')
        );

        /**
         * Inserting Imported Columns with new Ids and new assigned Imported Rows Ids
         */
        DB::insert(
            'insert into `imported_columns` (id, imported_row_id, importable_column_id, value, header)
            select uuid(), new_imported_row_id, importable_column_id, value, header from new_imported_columns
            '
        );

        return $quoteFileCopy;
    }
}
