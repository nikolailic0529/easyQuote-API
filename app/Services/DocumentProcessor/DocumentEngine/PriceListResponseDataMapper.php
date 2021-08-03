<?php

namespace App\Services\DocumentProcessor\DocumentEngine;

use App\Enum\Lock;
use App\Models\QuoteFile\ImportableColumn;
use App\Models\QuoteFile\ImportableColumnAlias;
use App\Models\QuoteFile\ImportedRow;
use App\Models\QuoteFile\QuoteFile;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;
use Webpatser\Uuid\Uuid;

class PriceListResponseDataMapper
{
    protected static string $reOnePay = '/\b(return to|RTS)\b/i';

    public function __construct(protected ConnectionInterface $connection,
                                protected LockProvider $lockProvider)
    {
    }

    public function updateDistributorQuoteFileData(QuoteFile $quoteFile, array $data): void
    {
        $lock = $this->lockProvider->lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10);

        $lock->block(30, function () use ($data, $quoteFile) {

            $this->connection->transaction(function () use ($data, $quoteFile) {

                $quoteFile->rowsData()->forceDelete();

                ImportedRow::query()->insert($data);

            });

        });
    }

    private function normalizeDistributorResponse(array $response): array
    {
        $responseAttributes = [];

        foreach ($response as $page) {
            $attributes = $page['attributes'] ?? [];

            $attributes = [
                'system_handle' => [$attributes['system_handle'] ?? null],
                'pricing_document' => [$attributes['pricing_document'] ?? null],
                'searchable' =>[$attributes['service_agreement_id'] ?? null],
            ];

            $responseAttributes = array_merge_recursive($responseAttributes, $attributes);
        }

        $responseAttributes = array_map(fn (array $values) => !empty(array_filter($values)), $responseAttributes);
        $responseAttributes = array_keys(array_filter($responseAttributes));

        return array_map(function (array $page) use ($responseAttributes) {
            $header = $page['header'] ?? [];
            $attributes = $page['attributes'] ?? [];
            $attributes = [
                'system_handle' => $attributes['system_handle'] ?? null,
                'pricing_document' => $attributes['pricing_document'] ?? null,
                'searchable' => $attributes['service_agreement_id'] ?? null,
            ];

            $attributes = with($attributes, function (array $attributes) use ($responseAttributes): array {
                $presentAttributes = [];

                foreach ($responseAttributes as $attributeName) {
                    $presentAttributes[$attributeName] = $attributes[$attributeName] ?? null;
                }

                return $presentAttributes;
            });

            if (!empty($attributes)) {
                $header = array_merge($header, [
                    'system_handle' => 'System Handle',
                    'pricing_document' => 'Pricing Document',
                    'searchable' => 'Service Agreement ID'
                ]);
            }

            $page['header'] = $header;
            $page['attributes'] = $attributes;

            return $page;
        }, $response);
    }

    public function mapDistributorResponse(QuoteFile $quoteFile, ?array $response): array
    {
        if (is_null($response)) {
            return [];
        }

        $response = $this->normalizeDistributorResponse($response);

        $rows = [];
        $currentPage = $quoteFile->imported_page ?? 1;

        foreach ($response as $page) {
            ['header' => $header, 'rows' => $pageRows, 'attributes' => $attributes] = $page;

            if ($pageRows === null) {
                $currentPage++;
                continue;
            }

            $pageRows = array_map(fn($row) => $row + $attributes, $pageRows);

            $allocatedColumns = [];

            $columns = array_map(function ($name) use (&$allocatedColumns) {
                [$column, $allocated] = $this->collateColumn($name);

                $allocatedColumns = $allocated;

                return $column;
            }, $header);

            foreach ($pageRows as $row) {
                $columnsData = collect($row)
                    ->filter(fn($value, $key) => isset($columns[$key]))
                    ->mapWithKeys(fn($value, $key) => [
                        $columns[$key] => [
                            'importable_column_id' => $columns[$key],
                            'header' => $header[$key] ?? $key,
                            'value' => $value,
                        ],
                    ]);

                $rows[] = [
                    'id' => (string)Uuid::generate(4),
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

    protected function isOnePayLine(array $line): bool
    {
        return (bool)preg_grep(static::$reOnePay, Arr::flatten($line));
    }

    protected function collateColumn(string $header, array $allocatedColumns = null): array
    {
        $allocatedColumns ??= [];

        $alias = Str::of($header)->trim()->lower();

        // Looking for an importable column with exact alias matching.
        $column = ImportableColumn::query()
            ->whereHas('aliases', fn(Builder $query) => $query->where('alias', $alias))
            ->when($allocatedColumns, fn(Builder $query) => $query->whereKeyNot($allocatedColumns))
            ->orderByDesc('is_system')
            ->orderBy('is_temp')
            ->first(['id']);

        // If an importable column hasn't being found,
        // we will create a new one importable column with the respective alias.
        if (is_null($column)) {
            $column = ImportableColumn::query()->make([
                'header' => $header,
                'name' => Str::slug($header, '_'),
                'is_temp' => true,
            ]);

            $column->disableLogging();
            $column->disableReindex();
            $column->save();

            tap(new ImportableColumnAlias(), function (ImportableColumnAlias $columnAlias) use ($column, $alias) {
                $columnAlias->id = (string)Uuid::generate(4);
                $columnAlias->importable_column_id = $column->getKey();
                $columnAlias->alias = $alias;

                $columnAlias->save();
            });
        }

        // Add allocated importable column id to array.
        $allocatedColumns[] = $column->getKey();

        return [$column->getKey(), $allocatedColumns];
    }
}
