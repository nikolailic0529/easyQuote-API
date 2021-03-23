<?php

namespace App\Services\DocumentProcessor;

use App\Contracts\Services\ManagesDocumentProcessors;
use App\Contracts\Services\ProcessesQuoteFile;
use App\DTO\MappedRow as MappedRowDTO;
use App\DTO\MappedRowSettings;
use App\DTO\RowMapping;
use App\Enum\Lock;
use App\Jobs\RetrievePriceAttributes;
use App\Models\{Quote\Quote, QuoteFile\QuoteFile,};
use App\Models\QuoteFile\ImportableColumn;
use App\Models\QuoteFile\ImportedRow;
use App\Models\QuoteFile\MappedRow;
use App\Models\Template\TemplateField;
use App\Support\PriceParser;
use Devengine\AnyDateParser\DateParser;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\{Carbon, Manager, Str};
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;
use Throwable;

class DocumentProcessor extends Manager implements ManagesDocumentProcessors
{
    protected ConnectionInterface $connection;

    protected LockProvider $lockProvider;

    public function __construct(Container $container, ConnectionInterface $connection, LockProvider $lockProvider)
    {
        parent::__construct($container);
        $this->connection = $connection;
        $this->lockProvider = $lockProvider;
    }

    public function performProcess(Quote $quote, QuoteFile $quoteFile, ?int $importablePageNumber = null)
    {
        $this->handleOrRetrieve($quote, $quoteFile, $importablePageNumber);

        $quoteFile->throwExceptionIfExists();

        if ($quoteFile->isPrice() && $quoteFile->isNotAutomapped()) {
            $this->mapColumnsToFields($quote, $quoteFile);
            dispatch(new RetrievePriceAttributes($quote->activeVersionOrCurrent));
        }

        return $quoteFile->processing_state;
    }

    public function forwardProcessor(QuoteFile $quoteFile): void
    {
        $ext = strtr($quoteFile->format->extension, [
            'xlsx' => 'excel',
            'xls' => 'excel',
            'doc' => 'word',
            'docx' => 'word',
        ]);

        $processorName = Str::snake($quoteFile->file_type.' '.$ext);

        $processor = $this->createDriver($processorName);

        $processor->process($quoteFile);
    }

    public function transitImportedRowsToMappedRows(QuoteFile $quoteFile, RowMapping $rowMapping, ?MappedRowSettings $rowSettings = null)
    {
        $rowSettings ??= new MappedRowSettings;

        /** @var MappedRow[] */
        $mappedRows = [];

        $quoteFile->rowsData()
            ->where('page', '>=', $quoteFile->imported_page)
            ->chunk(100, function (Collection $rows) use (&$mappedRows, $rowMapping, $rowSettings) {
                foreach ($rows as $row) {
                    $mappedRow = $this->collateImportedRow($row, $rowMapping, $rowSettings);

                    if (false === $this->ensureAnyRequiredFieldPresentOnMappedRow($mappedRow)) {
                        continue;
                    }

                    array_push($mappedRows, $mappedRow);
                }
            });

        $mappedRows = array_map(function (MappedRowDTO $mappedRow) use ($quoteFile) {
            return [
                    'id' => (string)Str::uuid(),
                    'quote_file_id' => $quoteFile->getKey(),
                ] + $mappedRow->toArray();
        }, $mappedRows);

        $lock = $this->lockProvider->lock(
            Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()),
            10
        );

        $lock->block(30);

        $this->connection->beginTransaction();

        try {
            $quoteFile->mappedRows()->delete();

            MappedRow::query()->insert($mappedRows);

            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        } finally {
            $lock->release();
        }
    }

    protected function collateImportedRow(ImportedRow $row, RowMapping $mapping, MappedRowSettings $rowSettings): MappedRowDTO
    {
        $productNo = transform(data_get($row->columns_data, $mapping->product_no.".value"), fn($value) => (string)$value);
        $serviceSKU = transform(data_get($row->columns_data, $mapping->service_sku.".value"), fn($value) => (string)$value);
        $description = transform(data_get($row->columns_data, $mapping->description.".value"), fn($value) => (string)$value);
        $serialNo = transform(data_get($row->columns_data, $mapping->serial_no.".value"), fn($value) => (string)$value);
        $dateFrom = data_get($row->columns_data, $mapping->date_from.".value");
        $dateTo = data_get($row->columns_data, $mapping->date_to.".value");
        $quantity = data_get($row->columns_data, $mapping->qty.".value");
        $price = data_get($row->columns_data, $mapping->price.".value");
        $pricingDocument = transform(data_get($row->columns_data, $mapping->pricing_document.".value"), fn($value) => (string)$value);
        $systemHandle = transform(data_get($row->columns_data, $mapping->system_handle.".value"), fn($value) => (string)$value);
        $searchable = transform(data_get($row->columns_data, $mapping->searchable.".value"), fn($value) => (string)$value);
        $serviceLevelDescription = transform(data_get($row->columns_data, $mapping->service_level_description.".value"), fn($value) => (string)$value);

        $parseDate = function (?string $date) {
            if (is_null($date)) {
                return null;
            }

            if (preg_match('/^[0-9]{5}(\.\d{9})?$/', $date)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($date));
            }

            return (new DateParser($date))->parseSilent();
        };

        $dateFrom = $parseDate($dateFrom) ?? $rowSettings->default_date_from;
        $dateTo = $parseDate($dateTo) ?? $rowSettings->default_date_to;

        $quantity = with($quantity, function ($quantity) use ($rowSettings) {
            if (blank($quantity)) {
                return $rowSettings->default_qty;
            }

            return (int)$quantity;
        });

        $price = (float)transform($price, function ($price) use ($dateTo, $dateFrom, $row, $rowSettings) {
            $value = PriceParser::parseAmount($price);

            if ($row->is_one_pay || false === $rowSettings->calculate_list_price) {
                return $value;
            }

            return $dateFrom->diffInMonths($dateTo) * $value;
        });

        $price *= $rowSettings->exchange_rate_value;

        return new MappedRowDTO([
            'product_no' => $productNo,
            'service_sku' => $serviceSKU,
            'description' => $description,
            'serial_no' => $serialNo,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'qty' => $quantity,
            'price' => $price,
            'pricing_document' => $pricingDocument,
            'system_handle' => $systemHandle,
            'searchable' => $searchable,
            'service_level_description' => $serviceLevelDescription,
        ]);
    }

    protected function ensureAnyRequiredFieldPresentOnMappedRow(MappedRowDTO $mappedRow): bool
    {
        if (!is_null($mappedRow->product_no) && trim($mappedRow->product_no) !== '') {
            return true;
        }

        if (!is_null($mappedRow->description) && trim($mappedRow->description) !== '') {
            return true;
        }

        if (!is_null($mappedRow->serial_no) && trim($mappedRow->serial_no) !== '') {
            return true;
        }

        return false;
    }

    public function mapColumnsToFields(Quote $quote, QuoteFile $quoteFile): void
    {
        $quoteLock = $this->lockProvider->lock(Lock::UPDATE_QUOTE($quote->getKey()), 10);

        $quoteLock->block(30);

        try {
            $templateFields = TemplateField::query()->pluck('id', 'name');

            $row = $quoteFile->rowsData()
                ->where('page', '>=', $quoteFile->imported_page ?? 1)
                ->first();

            $columns = optional($row)->columns_data;

            if (blank($columns)) {
                $quote->activeVersionOrCurrent->templateFields()->detach();

                $quoteFileLock = $this->lockProvider->lock(
                    Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()),
                    10
                );

                $quoteFileLock->block(30, $quoteFile->markAsAutomapped());
                return;
            }

            $defaultAttributes = [
                'is_default_enabled' => false,
                'is_preview_visible' => true,
                'default_value' => null,
                'sort' => null,
            ];

            $importableColumns = ImportableColumn::whereKey($columns->pluck('importable_column_id'))->pluck('id', 'name');

            $map = $templateFields
                ->mergeRecursive($importableColumns)
                ->filter(fn($map) => is_array($map) && count($map) === 2)
                ->mapWithKeys(fn($map, $key) => [
                    $map[0] => ['importable_column_id' => $map[1]] + $defaultAttributes,
                ]);

            $quote->activeVersionOrCurrent->templateFields()->sync($map->toArray());

            $quoteFileLock = $this->lockProvider->lock(
                Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()),
                10
            );

            $quoteFileLock->block(30, $quoteFile->markAsAutomapped());
        } finally {
            $quoteLock->release();
        }
    }

    protected function handleOrRetrieve(Quote $quote, QuoteFile $quoteFile, ?int $importablePageNumber = null): bool
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

        $version = $quote->activeVersionOrCurrent;

        $lock = $this->lockProvider->lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10);
        $lock->block(30);

        try {
            if (!is_null($importablePageNumber)) {
                $quoteFile->setImportedPage($importablePageNumber);
            }

            $quoteFile->clearException();

            if ($quoteFile->isPrice()) {
                $version->priceList()->associate($quoteFile)->save();
                $version->forgetCachedMappingReview();
                $version->resetGroupDescription();
            }

            if ($quoteFile->isSchedule()) {
                $version->paymentSchedule()->associate($quoteFile)->save();
            }
        } finally {
            $lock->release();
        }

        $this->forwardProcessor($quoteFile);

        if ($quoteFile->isPrice() && $quoteFile->rowsData()->where('page', '>=', $quoteFile->imported_page)->doesntExist()) {
            $quoteFile->setException(QFNRF_02, 'QFNRF_02');
        }

        if ($quoteFile->isSchedule() && (is_null($quoteFile->scheduleData) || blank($quoteFile->scheduleData->value))) {
            $quoteFile->setException(QFNS_01, 'QFNS_01');
        }

        if ($quoteFile->isPrice()) {
            $this->mapColumnsToFields($quote, $quoteFile);
        }

        return true;
    }

    public function getDefaultDriver()
    {
        throw new RuntimeException("The Document Processor must be explicitly defined");
    }

    public function driver($driver = null): ProcessesQuoteFile
    {
        return parent::driver($driver);
    }
}
