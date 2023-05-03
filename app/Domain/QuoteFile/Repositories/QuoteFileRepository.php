<?php

namespace App\Domain\QuoteFile\Repositories;

use App\Domain\DocumentProcessing\Readers\Pdf\PdfOptions;
use App\Domain\QuoteFile\Contracts\{QuoteFileRepositoryInterface};
use App\Domain\QuoteFile\Models\DataSelectSeparator;
use App\Domain\QuoteFile\Models\ImportedRow;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\Sync\Enum\Lock;
use Illuminate\Database\Eloquent\Collection as DbCollection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;

class QuoteFileRepository implements QuoteFileRepositoryInterface
{
    protected \App\Domain\QuoteFile\Models\QuoteFile $quoteFile;

    protected DataSelectSeparator $dataSelectSeparator;

    protected static ?DbCollection $importableColumnsCache = null;

    public function __construct(
        QuoteFile $quoteFile,
        DataSelectSeparator $dataSelectSeparator
    ) {
        $this->quoteFile = $quoteFile;
        $this->dataSelectSeparator = $dataSelectSeparator;
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

    public function createRawData(QuoteFile $quoteFile, array $array)
    {
        $user = $quoteFile->user;
        $rawFilesDirectory = "{$user->quoteFilesDirectory}/raw";

        $rawData = collect($array)->map(function ($data) use ($quoteFile, $user, $rawFilesDirectory) {
            $filePath = "{$rawFilesDirectory}/{$quoteFile->id}@{$data['page']}.txt";

            Storage::put($filePath, $data['content']);

            $importedRawData = $quoteFile->importedRawData()->make([
                'page' => $data['page'],
                'file_path' => $filePath,
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

    public function createScheduleData(QuoteFile $quoteFile, array $value)
    {
        $user = $quoteFile->user;

        $lock = Cache::lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10);

        $lock->block(30);

        DB::beginTransaction();

        try {
            if ($quoteFile->scheduleData()->exists()) {
                $quoteFile->scheduleData()->forceDelete();
            }

            $scheduleData = $quoteFile->scheduleData()->make(compact('value'));
            $scheduleData->user()->associate($user);
            $scheduleData->save();

            $quoteFile->markAsHandled();

            DB::commit();

            return $scheduleData;
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        } finally {
            optional($lock)->release();
        }
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
        return $this->quoteFile->query()->whereKey($id)->firstOrFail();
    }

    public function findByClause(array $clause)
    {
        return $this->quoteFile->query()->where($clause)->first();
    }

    public function findByQuote(string $quoteID, string $fileType)
    {
        return $this->quoteFile->query()
            ->whereExists(fn (BaseBuilder $builder) => $builder->selectRaw('1')->from('quotes')
                ->where('id', $quoteID)
                ->where(fn (BaseBuilder $builder) => $builder->whereColumn('quote_files.id', 'quotes.distributor_file_id')->orWhereColumn('quote_files.id', 'quotes.schedule_file_id')))
            ->where('file_type', $fileType)
            ->first();
    }

    public function exists(string $id)
    {
        return $this->quoteFile->whereId($id)->exists();
    }

    public function replicatePriceList(QuoteFile $quoteFile, ?string $quoteId = null): QuoteFile
    {
        $quote_file_id = $quoteFile->id;

        /** @var QuoteFile */
        $quoteFileCopy = tap($quoteFile->replicate(), function ($file) {
            $file->save();
        });

        $new_quote_file_id = $quoteFileCopy->getKey();

        $tempRowsTable = 'temp_imported_rows_table_'.uniqid();
        $importedRowColumn = 'replicated_row_id';

        /* Generating new Ids for Imported Columns and Rows in the temporary table. */
        DB::statement(
            "create temporary table `{$tempRowsTable}`
            select uuid() as `id`, `id` as {$importedRowColumn}, `quote_file_id`, `columns_data`, `page`, `is_selected` from `imported_rows`
                where `imported_rows`.`quote_file_id` = :quote_file_id",
            compact('quote_file_id')
        );

        /* We are marking temporary rows as selected if the related payload is present in the current request. */
        $this->updateTempSelectedImportedRows($tempRowsTable, $importedRowColumn);

        /* Inserting Imported Rows with new Ids. */
        DB::insert(
            "insert into `imported_rows` (`id`, `{$importedRowColumn}`,`quote_file_id`, `columns_data`, `page`, `is_selected`)
            select `id`, `{$importedRowColumn}`, :new_quote_file_id, `columns_data`, `page`, `is_selected`
            from `{$tempRowsTable}`",
            compact('new_quote_file_id')
        );

        return $quoteFileCopy;
    }

    public function resolveFileType(string $needle): ?string
    {
        return match ($needle) {
            'price' => QFT_PL,
            'schedule' => QFT_PS,
        };
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

        if (empty($importedRows)) {
            return;
        }

        $rows = array_map(fn (\App\Domain\QuoteFile\Models\ImportedRow $row) => $row->getAttributes() + [
                'id' => (string) Uuid::generate(4),
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ], $importedRows);

        \App\Domain\QuoteFile\Models\ImportedRow::insert($rows);
    }

    protected function createImportedRows(array $rows, QuoteFile $quoteFile, int $page): Collection
    {
        $quote_file_id = $quoteFile->id;
        $user_id = $quoteFile->user->id;
        $attributes = compact('quote_file_id', 'user_id', 'page');

        return collect($rows)
            ->map(fn ($row) => ImportedRow::make(
                $attributes + [
                    'columns_data' => $this->mapColumnsData($row),
                    'is_one_pay' => (bool) Arr::get($row, PdfOptions::SYSTEM_HEADER_ONE_PAY),
                ]
            ));
    }

    protected function mapColumnsData(array $row): Collection
    {
        $importableColumns = $this->getImportableColumns();

        Arr::forget($row, PdfOptions::SYSTEM_HEADER_ONE_PAY);

        return Collection::wrap($row)->map(
            fn ($value, $name) => [
                'header' => Str::header($name),
                'value' => $value,
                'importable_column_id' => optional($importableColumns->get($name))->id,
            ]
        )->values();
    }

    protected function getImportableColumns(): DbCollection
    {
        if (isset(static::$importableColumnsCache)) {
            return static::$importableColumnsCache;
        }

        return static::$importableColumnsCache = \App\Domain\QuoteFile\Models\ImportableColumn::query()
            ->orderBy('order')
            ->where('is_system', true)
            ->with('aliases')
            ->get()
            ->keyBy('name');
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
}
