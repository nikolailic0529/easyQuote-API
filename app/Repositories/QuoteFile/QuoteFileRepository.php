<?php

namespace App\Repositories\QuoteFile;

use App\Contracts\Repositories\QuoteFile\{
    QuoteFileRepositoryInterface,
    ImportableColumnRepositoryInterface as ImportableColumnRepository,
    FileFormatRepositoryInterface as FileFormatRepository
};
use App\Models\{
    Quote\BaseQuote as Quote,
    QuoteFile\QuoteFile,
    QuoteFile\ImportedColumn,
    QuoteFile\DataSelectSeparator,
    QuoteFile\ImportedRow
};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
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

        $user = app()->runningInConsole() && isset($attributes['user'])
            ? $attributes['user']
            : request()->user();

        $quoteFile = $user->quoteFiles()->make(
            array_merge($attributes, compact('original_file_name'))
        );

        if (isset($attributes['format'])) {
            $quoteFile->format()->associate($attributes['format']);
        }

        $quoteFile->markAsDrafted();

        if (($quoteFile->isPdf() || $quoteFile->isWord()) && isset($attributes['rawData'])) {
            $this->createRawData($quoteFile, $attributes['rawData']);
        }


        return $quoteFile->load('dataSelectSeparator')->makeHidden('user');
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

        $attributes = compact('quote_file', 'format', 'file_type', 'original_file_path', 'quote_id');

        if (app()->runningInConsole()) {
            $attributes = array_merge($attributes, ['user' => $quote->user]);
        }

        $quoteFile = $this->create($attributes);

        $quote->load('generatedPdf');

        return $quoteFile;
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
        return $quoteFile->importedRawData()->orderBy('page')->get();
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
        $user = $quoteFile->user;

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
            $quoteFile->setException(QFNRF_01, 'QFNRF_01');
            $quoteFile->markAsUnHandled();
            $quoteFile->throwExceptionIfExists();
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
            $header = Str::header($columnName, QFUH_01, $formatHeader);

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

    public function find(string $id)
    {
        return $this->quoteFile->query()->whereId($id)->firstOrFail();
    }

    public function exists(string $id)
    {
        return $this->quoteFile->whereId($id)->exists();
    }

    public function deletePriceListsExcept(QuoteFile $quoteFile)
    {
        $exceptedQuoteFileId = $quoteFile->id;
        $quote = $quoteFile->quote;

        return $quote->quoteFiles()->priceLists()->whereKeyNot($exceptedQuoteFileId)->delete();
    }

    public function deletePaymentSchedulesExcept(QuoteFile $quoteFile)
    {
        $exceptedQuoteFileId = $quoteFile->id;
        $quote = $quoteFile->quote;

        return $quote->quoteFiles()->paymentSchedules()->whereKeyNot($exceptedQuoteFileId)->delete();
    }

    public function deleteExcept(QuoteFile $quoteFile)
    {
        if ($quoteFile->isSchedule()) {
            return $this->deletePaymentSchedulesExcept($quoteFile);
        }

        return $this->deletePriceListsExcept($quoteFile);
    }

    public function replicatePriceList(QuoteFile $quoteFile, ?string $quoteId = null): QuoteFile
    {
        $quote_file_id = $quoteFile->id;
        $user_id = $quoteFile->user_id;

        $quoteFileCopy = tap($quoteFile->replicate(), function ($file) use ($quoteId) {
            $file->quote()->associate($quoteId);
            $file->saveOrFail();
        });
        $new_quote_file_id = $quoteFileCopy->id;

        $tempTable = 'temp_import_table_' . uniqid();
        $importedRowColumn = 'temp_imported_row_id';

        /** Generating new Ids for Imported Columns and Rows in the temporary table. */
        DB::select(
            "create temporary table `{$tempTable}`
            select * from `imported_columns`
            join (select uuid() as new_imported_row_id, imported_row_id as {$importedRowColumn}
                from `imported_columns`
                group by imported_row_id) as temp_imported_columns
                on temp_imported_columns.{$importedRowColumn} = `imported_columns`.imported_row_id
            join (select id as row_id, quote_file_id, page, is_selected, processed_at, group_name from imported_rows) as imported_rows
                on imported_rows.row_id = `imported_columns`.imported_row_id
                where imported_rows.quote_file_id = :quote_file_id",
            compact('quote_file_id')
        );

        /** We are marking temporary rows as selected if the related payload is present in the current request. */
        $this->updateTempSelectedImportedRows($tempTable, $importedRowColumn);

        /** We are updating temporary group description if the related payload is present in the current request. */
        $this->updateTempGroupDescription($tempTable, $importedRowColumn);

        /** Inserting Imported Rows with new Ids. */
        DB::insert(
            "insert into `imported_rows` (id, user_id, quote_file_id, page, is_selected, processed_at, group_name)
            select new_imported_row_id, :user_id, :new_quote_file_id, page, is_selected, processed_at, group_name
            from `{$tempTable}` group by new_imported_row_id",
            compact('user_id', 'new_quote_file_id')
        );

        /** Inserting Imported Columns with new Ids and new assigned Imported Rows Ids. */
        DB::insert(
            "insert into `imported_columns` (id, imported_row_id, importable_column_id, value, header)
            select uuid(), new_imported_row_id, importable_column_id, value, header from `{$tempTable}`"
        );

        return $quoteFileCopy;
    }

    private function updateTempSelectedImportedRows(string $table, string $column): void
    {
        if (request()->missing('quote_data.selected_rows') && request()->missing('quote_data.selected_rows_is_rejected')) {
            return;
        }

        $selectedRowsIds = (array) request()->input('quote_data.selected_rows', []);
        $reject = (bool) request()->input('quote_data.selected_rows_is_rejected', false);

        $updatableScope = $reject ? 'whereNotIn' : 'whereIn';

        DB::table($table)->update(['is_selected' => false]);

        DB::table($table)->{$updatableScope}($column, $selectedRowsIds)->update(['is_selected' => true]);
    }

    private function updateTempGroupDescription(string $table, string $column): void
    {
        if (!request()->is('api/quotes/groups/*')) {
            return;
        }

        $rowsIds = request()->input('rows', []);
        $name = request()->input('name');

        /** Create Group Description request */
        if (request()->isMethod('post')) {
            DB::table($table)->whereIn($column, $rowsIds)->update(['group_name' => $name]);
        }

        /** Update Group Description request */
        if (request()->isMethod('patch')) {
            DB::table($table)->where('group_name', request()->group_name)->update(['group_name' => null]);
            DB::table($table)->whereIn($column, $rowsIds)->update(['group_name' => $name]);
        }

        /** Move Group Description rows request */
        if (request()->isMethod('put')) {
            DB::table($table)->where('group_name', request()->from_group_name)->whereIn($column, $rowsIds)
                ->update(['group_name' => request()->to_group_name]);
        }
    }
}
