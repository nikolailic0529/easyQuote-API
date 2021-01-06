<?php

namespace App\Services\DocumentProcessor\DocumentEngine\Concerns;

use App\Models\QuoteFile\QuoteFile;
use App\Models\QuoteFile\ImportedRow;
use App\Models\QuoteFile\ImportableColumn;
use App\Models\QuoteFile\ImportableColumnAlias;
use App\Enum\Lock;
use Webpatser\Uuid\Uuid;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

trait UpdatesDistributorFileData
{
    protected static $reOnePay = '/\b(return to|RTS)\b/i';

    protected function updateDistributorQuoteFileData(QuoteFile $quoteFile, array $data): void
    {
        $lock = Cache::lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10);
        $lock->block(30);

        DB::beginTransaction();

        try {
            $quoteFile->rowsData()->forceDelete();

            ImportedRow::insert($data);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        } finally {
            optional($lock)->release();
        }
    }

    protected function mapDistributorResponse(QuoteFile $quoteFile, ?array $response)
    {
        if (is_null($response)) {
            return [];
        }

        $rows = [];
        $currentPage = $quoteFile->imported_page ?? 1;

        foreach ($response as $page) {
            ['header' => $header, 'rows' => $pageRows, 'attributes' => $attributes] = $page;

            if ($pageRows === null) {
                $currentPage++;
                continue;
            }

            $columns = collect($header);

            if (!empty($attributes) && is_array($attributes)) {
                $attributes = [
                    'system_handle' => Arr::get($attributes, 'system_handle'),
                    'pricing_document' => Arr::get($attributes, 'pricing_document'),
                    'searchable' => Arr::get($attributes, 'service_agreement_id'),
                ];

                $columns = $columns->merge(
                    collect($attributes)->keys()
                        ->mapWithKeys(
                            fn ($key) => [$key => (string) Str::of($key)->title()->replace('_', ' ')]
                        )
                );
            }

            $pageRows = array_map(fn ($row) => $row + $attributes, $pageRows);

            $allocatedColumns = [];

            $columns = $columns
                ->map(function ($name, $key) use (&$allocatedColumns) {
                    [$column, $allocated] = $this->collateColumn($name);

                    $allocatedColumns = $allocated;

                    return $column;
                });

            foreach ($pageRows as $row) {
                $columnsData = collect($row)->mapWithKeys(fn ($value, $key) => [
                    $columns[$key] => [
                        'importable_column_id' => $columns[$key],
                        'header' => $header[$key] ?? $key,
                        'value' => $value,
                    ]
                ]);

                $rows[] = [
                    'id' => (string) Uuid::generate(4),
                    'quote_file_id' => $quoteFile->getKey(),
                    'page' => $currentPage,
                    'columns_data' => $columnsData->toJson(),
                    'is_one_pay' => $this->isOnePayLine($row),
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ];
            }

            $currentPage++;
        }

        return $rows;
    }

    protected function isOnePayLine(array $line)
    {
        return (bool) preg_grep(static::$reOnePay, Arr::flatten($line));
    }

    protected function collateColumn(string $header, array $allocatedColumns = null)
    {
        $alias = Str::of($header)->trim()->lower();

        // Looking for an importable column with exact alias matching.
        $column = ImportableColumn::whereHas('aliases', fn (Builder $query) => $query->where('alias', $alias))
            ->when($allocatedColumns, fn (Builder $query) => $query->whereKeyNot($allocatedColumns))
            ->orderByDesc('is_system')
            ->orderBy('is_temp')
            ->first(['id']);

        // If an importable column hasn't being found,
        // we will create a new one importable column with the respective alias.
        if (is_null($column)) {
            $column = ImportableColumn::make([
                'header' => $header,
                'name' => Str::slug($header, '_'),
                'is_temp' => true,
            ]);

            $column->disableLogging();
            $column->disableReindex();
            $column->save();

            ImportableColumnAlias::make([
                'id' => (string) Uuid::generate(4),
                'importable_column_id' => $column->getKey(),
                'alias' => $alias,
            ])->save();
        }

        // Add allocated importable column id to array.
        $allocatedColumns[] = $column->getKey();

        return [$column->getKey(), $allocatedColumns];
    }
}
