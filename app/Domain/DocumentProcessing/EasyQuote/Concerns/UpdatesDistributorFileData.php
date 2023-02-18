<?php

namespace App\Domain\DocumentProcessing\EasyQuote\Concerns;

use App\Domain\QuoteFile\Models\ImportableColumn;
use App\Domain\QuoteFile\Models\ImportedRow;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\Sync\Enum\Lock;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait UpdatesDistributorFileData
{
    protected function updateDistributorQuoteFileData(QuoteFile $quoteFile, array $pages, array $attributes): void
    {
        $lock = Cache::lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10);

        $lock->block(30);

        DB::beginTransaction();

        try {
            $quoteFile->rowsData()->forceDelete();
            $quoteFile->forceFill(['meta_attributes' => null]);

            if (!empty($attributes)) {
                $quoteFile->forceFill(
                    ['meta_attributes' => json_encode($attributes), 'handled_at' => now()]
                );
            }

            $quoteFile->save();

            $rows = $this->mapDistributorPagesToRows($quoteFile->getKey(), $pages);

            if (!empty($rows)) {
                ImportedRow::insert($rows);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        } finally {
            $lock->release();
        }
    }

    protected function mapDistributorPagesToRows(string $quoteFileId, array $pages)
    {
        $columns = ImportableColumn::where('is_system', true)->pluck('id', 'name');

        return array_reduce($pages, function (array $rows, array $page) use ($quoteFileId, $columns) {
            ['page' => $pageNumber, 'rows' => $pageRows] = $page;

            foreach ($pageRows as $row) {
                $onePay = (bool) Arr::pull($row, '_one_pay');

                $columnsData = collect($row)->mapWithKeys(function ($value, $key) use ($columns) {
                    return [$columns[$key] => [
                        'header' => (string) Str::of($key)->replace('_', ' ')->title(),
                        'value' => $value,
                        'importable_column_id' => $columns[$key],
                    ]];
                })->toJson();

                array_push($rows, [
                    'id' => (string) Str::uuid(4),
                    'quote_file_id' => $quoteFileId,
                    'columns_data' => $columnsData,
                    'is_one_pay' => $onePay,
                    'page' => $pageNumber,
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ]);
            }

            return $rows;
        }, []);
    }
}
