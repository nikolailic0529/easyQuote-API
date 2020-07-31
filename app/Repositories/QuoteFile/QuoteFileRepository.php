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
    QuoteFile\DataSelectSeparator,
    QuoteFile\ImportedRow
};
use App\Services\PdfParser\PdfOptions;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as SupportCollection;
use Storage, Str, File, DB;

class QuoteFileRepository implements QuoteFileRepositoryInterface
{
    protected QuoteFile $quoteFile;

    protected FileFormatRepository $fileFormat;

    protected DataSelectSeparator $dataSelectSeparator;

    protected ImportableColumnRepository $importableColumn;

    protected static ?Collection $importableColumnsCache = null;

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
        DB::transaction(
            fn () => $quoteFile->rowsData()->forceDelete(),
            5
        );

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

    public function findByClause(array $clause)
    {
        return $this->quoteFile->query()->where($clause)->first();
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

        /** @var QuoteFile */
        $quoteFileCopy = tap($quoteFile->replicate(), function ($file) use ($quoteId) {
            $file->quote()->associate($quoteId);
            $file->save();
        });

        $new_quote_file_id = $quoteFileCopy->getKey();

        $tempRowsTable = 'temp_imported_rows_table_' . uniqid();
        $importedRowColumn = 'replicated_row_id';

        /** Generating new Ids for Imported Columns and Rows in the temporary table. */
        DB::select(
            "create temporary table `{$tempRowsTable}`
            select uuid() as `id`, `id` as {$importedRowColumn}, `quote_file_id`, `columns_data`, `page`, `is_selected`, `group_name` from `imported_rows`
                where `imported_rows`.`quote_file_id` = :quote_file_id",
            compact('quote_file_id')
        );

        /** We are marking temporary rows as selected if the related payload is present in the current request. */
        $this->updateTempSelectedImportedRows($tempRowsTable, $importedRowColumn);

        /** We are updating temporary group description if the related payload is present in the current request. */
        // $this->updateTempGroupDescription($tempRowsTable, $importedRowColumn);

        /** Inserting Imported Rows with new Ids. */
        DB::insert(
            "insert into `imported_rows` (`id`, `{$importedRowColumn}`, `user_id`, `quote_file_id`, `columns_data`, `page`, `is_selected`, `group_name`)
            select `id`, `{$importedRowColumn}`, :user_id, :new_quote_file_id, `columns_data`, `page`, `is_selected`, `group_name`
            from `{$tempRowsTable}`",
            compact('user_id', 'new_quote_file_id')
        );

        return $quoteFileCopy;
    }

    public function resolveFileType(string $needle): ?string
    {
        switch ($needle) {
            case 'price':
                return QFT_PL;
                break;
            case 'schedule':
                return QFT_PS;
                break;
        }
    }

    public function resolveFilepath(?QuoteFile $quoteFile): string
    {
        error_abort_if(is_null($quoteFile), QFNF_01, 'QFNF_01', 404);

        error_abort_if(Storage::missing($quoteFile->original_file_path), QFNF_02, 'QFNF_02', 404);

        return Storage::path($quoteFile->original_file_path);
    }

    protected function createImportedPages(array $array, QuoteFile $quoteFile)
    {
        $pages = collect($array)->filter(fn ($item) => (isset($item['page']) && isset($item['rows'])));

        $importedRows = [];

        $pages->each(function ($data) use (&$importedRows, $quoteFile) {
            ['page' => $page, 'rows' => $rows] = $data;

            array_push($importedRows, ...$this->createImportedRows($rows, $quoteFile, $page));
        });

        if (count($importedRows) < 1) {
            $quoteFile->setException(QFNRF_01, 'QFNRF_01');
            $quoteFile->markAsUnHandled();
            $quoteFile->throwExceptionIfExists();
        }

        $quoteFile->rowsData()->saveMany($importedRows);
    }

    protected function createImportedRows(array $rows, QuoteFile $quoteFile, int $page): SupportCollection
    {
        $quote_file_id = $quoteFile->id;
        $user_id = $quoteFile->user->id;
        $attributes = compact('quote_file_id', 'user_id', 'page');

        return collect($rows)
            ->map(fn ($row) => ImportedRow::make(
                $attributes + [
                    'columns_data' => $this->mapColumnsData($row),
                    'is_one_pay' => (bool) Arr::get($row, PdfOptions::SYSTEM_HEADER_ONE_PAY)
                ]
            ));
    }

    protected function mapColumnsData(array $row): SupportCollection
    {
        $importableColumns = $this->getImportableColumns();

        Arr::forget($row, PdfOptions::SYSTEM_HEADER_ONE_PAY);

        return SupportCollection::wrap($row)->map(
            fn ($value, $name) => [
                'header' => Str::header($name),
                'value' => $value,
                'importable_column_id' => optional($importableColumns->get($name))->id
            ]
        )->values();
    }

    protected function getImportableColumns(): Collection
    {
        if (isset(static::$importableColumnsCache)) {
            return static::$importableColumnsCache;
        }

        return static::$importableColumnsCache = $this->importableColumn->allSystem()->keyBy('name');
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
