<?php

namespace App\Domain\DocumentProcessing\DocumentEngine;

use App\Domain\QuoteFile\Models\ImportableColumn;
use App\Domain\QuoteFile\Models\ImportableColumnAlias;
use App\Domain\QuoteFile\Models\ImportedRow;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\Sync\Enum\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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

        $lock->block(30, function () use ($data, $quoteFile): void {
            $this->connection->transaction(static function () use ($data, $quoteFile): void {
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
                'searchable' => [$attributes['service_agreement_id'] ?? null],
            ];

            $responseAttributes = array_merge_recursive($responseAttributes, $attributes);
        }

        $responseAttributes = array_map(static fn (array $values) => !empty(array_filter($values)), $responseAttributes);
        $responseAttributes = array_keys(array_filter($responseAttributes));

        return array_map(static function (array $page) use ($responseAttributes) {
            $header = $page['header'] ?? [];
            $attributes = $page['attributes'] ?? [];
            $attributes = [
                'system_handle' => $attributes['system_handle'] ?? null,
                'pricing_document' => $attributes['pricing_document'] ?? null,
                'searchable' => $attributes['service_agreement_id'] ?? null,
            ];

            $attributes = with($attributes, static function (array $attributes) use ($responseAttributes): array {
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
                    'searchable' => 'Service Agreement ID',
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
                ++$currentPage;
                continue;
            }

            $pageRows = array_map(static fn ($row) => $row + $attributes, $pageRows);

            $allocatedColumns = [];

            $columns = array_map(function ($name) use (&$allocatedColumns) {
                [$column, $allocated] = $this->collateColumn($name, $allocatedColumns);

                $allocatedColumns = $allocated;

                return $column;
            }, $header);

            foreach ($pageRows as $row) {
                $columnsData = collect($row)
                    ->filter(static fn ($value, $key) => isset($columns[$key]))
                    ->mapWithKeys(static fn ($value, $key) => [
                        $columns[$key] => [
                            'importable_column_id' => $columns[$key],
                            'header' => $header[$key] ?? $key,
                            'value' => $value,
                        ],
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

            ++$currentPage;
        }

        return $rows;
    }

    protected function isOnePayLine(array $line): bool
    {
        return (bool) preg_grep(static::$reOnePay, Arr::flatten($line));
    }

    protected function collateColumn(string $header, array $allocatedColumns = null): array
    {
        $allocatedColumns ??= [];

        $alias = Str::of($header)->trim()->lower();

        $colModel = new ImportableColumn();
        $aliasModel = new ImportableColumnAlias();

        // Looking for an importable column with exact alias matching.
        $colId = $aliasModel->newQuery()
            ->where('alias', $alias)
            ->when($allocatedColumns, static function (Builder $query) use ($aliasModel, $allocatedColumns): void {
                $query->whereNotIn($aliasModel->importableColumn()->getForeignKeyName(), $allocatedColumns);
            })
            ->join($colModel->getTable(), $colModel->getQualifiedKeyName(), $aliasModel->importableColumn()->getQualifiedForeignKeyName())
            ->whereNull($colModel->getQualifiedDeletedAtColumn())
            ->orderByDesc($colModel->qualifyColumn('is_system'))
            ->orderBy($colModel->qualifyColumn('is_temp'))
            ->value($aliasModel->importableColumn()->getQualifiedForeignKeyName());

        // If an importable column hasn't being found,
        // we will create a new one importable column with the respective alias.
        if (is_null($colId)) {
            $column = ImportableColumn::query()->make([
                'header' => $header,
                'name' => Str::slug($header, '_'),
                'is_temp' => true,
            ]);

            $column->save();

            tap(new ImportableColumnAlias(), static function (ImportableColumnAlias $columnAlias) use ($column, $alias): void {
                $columnAlias->id = (string) Uuid::generate(4);
                $columnAlias->importable_column_id = $column->getKey();
                $columnAlias->alias = $alias;

                $columnAlias->save();
            });
        }

        // Add allocated importable column id to array.
        $allocatedColumns[] = $colId;

        return [$colId, $allocatedColumns];
    }
}
