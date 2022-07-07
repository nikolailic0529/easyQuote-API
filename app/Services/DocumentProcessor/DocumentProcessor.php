<?php

namespace App\Services\DocumentProcessor;

use App\Contracts\Repositories\SettingRepository;
use App\Contracts\Services\ManagesDocumentProcessors;
use App\Contracts\Services\ProcessesQuoteFile;
use App\DTO\MappedRowData;
use App\DTO\MappingConfig;
use App\DTO\RowMapping;
use App\Enum\DateFormatEnum;
use App\Enum\Lock;
use Carbon\Exceptions\InvalidFormatException;
use App\Models\{DocumentProcessLog, QuoteFile\QuoteFile};
use App\Models\QuoteFile\ImportedRow;
use App\Models\QuoteFile\MappedRow;
use App\Services\DocumentProcessor\Concerns\HasFallbackProcessor;
use App\Services\DocumentProcessor\Exceptions\DocumentComparisonException;
use App\Services\DocumentProcessor\Exceptions\NoDataFoundException;
use App\Support\PriceParser;
use Devengine\AnyDateParser\DateParser;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\{Carbon, Manager, Str};
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Webpatser\Uuid\Uuid;

class DocumentProcessor extends Manager implements ManagesDocumentProcessors
{
    protected LoggerInterface $logger;
    protected ConnectionInterface $connection;
    protected LockProvider $lockProvider;
    protected SettingRepository $settings;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->logger = $container->make('log')->channel('document-processor');
        $this->connection = $container->make(ConnectionInterface::class);
        $this->lockProvider = $container->make(LockProvider::class);
        $this->settings = $container->make(SettingRepository::class);
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @throws NoDataFoundException|DocumentComparisonException
     */
    public function forwardProcessor(QuoteFile $quoteFile): void
    {
        $ext = strtr($quoteFile->format->extension, [
            'xlsx' => 'excel',
            'xls' => 'excel',
            'doc' => 'word',
            'docx' => 'word',
        ]);

        $processorName = Str::snake($quoteFile->file_type.' '.$ext);

        $processor = $this->driver($processorName);

        /** @var DocumentProcessLog $processLog */
        $processLog = tap(new DocumentProcessLog(), function (DocumentProcessLog $log) use ($quoteFile, $processor) {

            $log->{$log->getKeyName()} = (string)Uuid::generate(4);
            $log->driver_id = (string)$processor->getProcessorUuid();
            $log->original_file_name = $quoteFile->original_file_name;
            $log->file_path = $quoteFile->original_file_path;
            $log->file_type = $quoteFile->file_type;

            $log->is_successful = false;

            $log->save();

        });

        try {
            $processor->process($quoteFile);

            with($processLog, function (DocumentProcessLog $log) {
                $log->is_successful = true;

                $log->save();
            });

            if ($processor instanceof HasFallbackProcessor) {
                $this->gracefullyProcessDocument($processor->getFallbackProcessor(), $quoteFile);
            }

        } catch (NoDataFoundException $e) {
            $this->logger->warning(sprintf("Failed to process file '%s' using %s processor.", $quoteFile->original_file_name, get_class($processor)));

            with($processLog, function (DocumentProcessLog $log) {

                $log->comment = 'No data found';

                $log->save();

            });

            if (!$processor instanceof HasFallbackProcessor) {
                $this->logger->warning(sprintf("File processor %s doesn't have a fallback processor.", get_class($processor)));

                throw $e;
            }

            $this->logger->info(sprintf("File processor %s has a fallback processor.", get_class($processor)));

            $fallbackProcessor = $processor->getFallbackProcessor();

            $fallbackProcessLog = tap(new DocumentProcessLog(), function (DocumentProcessLog $log) use ($quoteFile, $fallbackProcessor) {

                $log->{$log->getKeyName()} = (string)Uuid::generate(4);
                $log->driver_id = (string)$fallbackProcessor->getProcessorUuid();

                $log->original_file_name = $quoteFile->original_file_name;
                $log->file_path = $quoteFile->original_file_path;
                $log->file_type = $quoteFile->file_type;

                $log->is_successful = false;

                $log->save();

            });

            $this->logger->info(sprintf("Trying to process the file using %s processor...", get_class($fallbackProcessor)));

            $fallbackProcessor->process($quoteFile);

            with($fallbackProcessLog, function (DocumentProcessLog $log) {

                $log->is_successful = true;

                $log->save();

            });
        }
    }

    public function transitImportedRowsToMappedRows(QuoteFile $quoteFile, RowMapping $rowMapping, ?MappingConfig $mappingConfig = null)
    {
        $mappingConfig ??= new MappingConfig;

        /** @var MappedRow[] */
        $mappedRows = [];

        $quoteFile->rowsData()
            ->where('page', '>=', $quoteFile->imported_page)
            ->chunk(100, function (Collection $rows) use (&$mappedRows, $rowMapping, $mappingConfig) {
                foreach ($rows as $row) {
                    $mappedRow = $this->collateImportedRow($row, $rowMapping, $mappingConfig);

                    if (false === $this->ensureAnyRequiredFieldPresentOnMappedRow($mappedRow)) {
                        continue;
                    }

                    $mappedRows[] = $mappedRow;
                }
            });

        $mappedRows = array_map(function (MappedRowData $mappedRow) use ($quoteFile) {
            return [
                    'id' => (string)Str::uuid(),
                    'quote_file_id' => $quoteFile->getKey(),
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ] + $mappedRow->toArray();
        }, $mappedRows);

        $lock = $this->lockProvider->lock(
            Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()),
            10
        );

        $lock->block(30, function () use ($quoteFile, $mappedRows) {

            $this->connection->transaction(function () use ($quoteFile, $mappedRows) {

                $quoteFile->mappedRows()->delete();

                MappedRow::query()->insert($mappedRows);

            });

        });
    }

    protected function collateImportedRow(ImportedRow $row, RowMapping $mapping, MappingConfig $mappingConfig): MappedRowData
    {
        /** @var array $mappedRowData */
        $mappedRowData = [
            'product_no' => null,
            'service_sku' => null,
            'description' => null,
            'serial_no' => null,
            'date_from' => null,
            'date_to' => null,
            'qty' => null,
            'price' => null,
            'original_price' => null,
            'pricing_document' => null,
            'system_handle' => null,
            'searchable' => null,
            'service_level_description' => null,
        ];

        foreach (array_keys($mappedRowData) as $key) {
            if (isset($mapping->{$key})) {
                $mappedRowData[$key] = transform(data_get($row->columns_data, "{$mapping->{$key}}.value"), function ($value) {
                    return (string)$value;
                });
            }
        }

        $parseDate = function (?string $date) use ($mappingConfig) {
            if (blank($date)) {
                return null;
            }

            if (preg_match('/^[0-9]{5}(\.\d{9})?$/', $date)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($date));
            }

            if (DateFormatEnum::Auto === $mappingConfig->file_date_format) {
                return (new DateParser($date))->parseSilent();
            }

            try {
                return Carbon::createFromIsoFormat($mappingConfig->file_date_format->value, $date);
            } catch (InvalidFormatException) {
                return null;
            }
        };

        $dateFrom = $mappedRowData['date_from'] = $parseDate($mappedRowData['date_from']) ?? $mappingConfig->default_date_from;
        $dateTo = $mappedRowData['date_to'] = $parseDate($mappedRowData['date_to']) ?? $mappingConfig->default_date_to;

        $mappedRowData['qty'] = with($mappedRowData['qty'], function ($quantity) use ($mappingConfig) {
            if (blank($quantity)) {
                return $mappingConfig->default_qty;
            }

            return (int)$quantity;
        });

        $mappedRowData['original_price'] = (float)transform($mappedRowData['price'], function ($price) use ($dateFrom, $dateTo, $row, $mappingConfig) {
            $value = PriceParser::parseAmount($price);

            if ($row->is_one_pay || false === $mappingConfig->calculate_list_price) {
                return $value;
            }

            if ($mappingConfig->is_contract_duration_checked) {
                return $mappingConfig->contract_duration?->months * $value;
            }

            return $dateFrom->diffInMonths($dateTo) * $value;
        });

        $mappedRowData['price'] = $mappedRowData['original_price'] * $mappingConfig->exchange_rate_value;

        return new MappedRowData($mappedRowData);
    }

    protected function ensureAnyRequiredFieldPresentOnMappedRow(MappedRowData $mappedRow): bool
    {

        foreach ([
                     'product_no',
                     'description',
                     'serial_no',
                 ] as $fieldName) {

            if (trim((string)$mappedRow->{$fieldName}) !== '') {
                return true;
            }

        }

        return false;
    }

    public function getDefaultDriver()
    {
        throw new RuntimeException("The Document Processor must be explicitly defined");
    }

    public function driver($driver = null): ProcessesQuoteFile
    {
        return with(parent::driver($driver), function (ProcessesQuoteFile $processor) {

            if ($processor instanceof HasFallbackProcessor && (!$this->config['docprocessor.document_engine_enabled'] || $this->settings['use_legacy_doc_parsing_method'])) {

                return $processor->getFallbackProcessor();

            }

            return $processor;

        });
    }

    /**
     * Process the quote file entity with the specified processor,
     * and decide which data to use.
     *
     * @param \App\Contracts\Services\ProcessesQuoteFile $processor
     * @param \App\Models\QuoteFile\QuoteFile $quoteFile
     * @throws DocumentComparisonException|NoDataFoundException
     */
    protected function gracefullyProcessDocument(ProcessesQuoteFile $processor, QuoteFile $quoteFile): void
    {
        /** @var QuoteFile $bQuoteFile */
        $bQuoteFile = tap($quoteFile->replicate(), function (QuoteFile $quoteFile) {
            $quoteFile->{$quoteFile->getKeyName()} = (string)Uuid::generate(4);

            $quoteFile->save();
        });

        $this->logger->info("Trying to process a copy of the quote file with the fallback processor.", ['quote_file_id' => $quoteFile->getKey(), 'replicated_quote_file_id' => $bQuoteFile->getKey()]);

        $processor->process($bQuoteFile);

        $quoteFileWithMoreCompleteData = (new DocumentDataComparator())(aFile: $quoteFile, bFile: $bQuoteFile);

        // When the file with more complete data is the file, processed by the fallback processor,
        // we will process the original file using the fallback processor.
        if ($quoteFileWithMoreCompleteData->is($bQuoteFile)) {

            $processLog = tap(new DocumentProcessLog(), function (DocumentProcessLog $log) use ($quoteFile, $processor) {

                $log->{$log->getKeyName()} = (string)Uuid::generate(4);
                $log->driver_id = (string)$processor->getProcessorUuid();
                $log->original_file_name = $quoteFile->original_file_name;
                $log->file_path = $quoteFile->original_file_path;
                $log->file_type = $quoteFile->file_type;

                $log->is_successful = false;

                $log->save();

            });

            $this->logger->info("Decided to use data for quote file from fallback processor.");

            $processor->process($quoteFile);

            with($processLog, function (DocumentProcessLog $log) {

                $log->comment = 'More complete data found';
                $log->is_successful = true;

                $log->save();

            });

        } else {

            $this->logger->info("Decided to keep the original data of the quote file.");

        }


        // Then we will delete the replicated quote file.
        $bQuoteFile->forceDelete();
    }
}
