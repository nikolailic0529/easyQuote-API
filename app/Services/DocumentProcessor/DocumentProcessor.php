<?php

namespace App\Services\DocumentProcessor;

use App\Contracts\Repositories\QuoteTemplate\TemplateFieldRepositoryInterface as TemplateFields;
use App\Contracts\Services\ManagesDocumentProcessors;
use App\Contracts\Services\ProcessesQuoteFile;
use App\Enum\Lock;
use App\Models\{
    Quote\Quote,
    QuoteFile\QuoteFile,
};
use App\Jobs\RetrievePriceAttributes;
use Illuminate\Pipeline\Pipeline;
use App\Models\QuoteFile\ImportableColumn;
use Illuminate\Support\{
    Facades\Cache,
    Manager,
    Str,
};
use RuntimeException;

class DocumentProcessor extends Manager implements ManagesDocumentProcessors
{
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

    public function mapColumnsToFields(Quote $quote, QuoteFile $quoteFile): void
    {
        $quoteLock = Cache::lock(Lock::UPDATE_QUOTE($quote->getKey()), 10);

        $quoteLock->block(30);

        try {
            $templateFields = app(TemplateFields::class)->allSystem();

            $row = $quoteFile->rowsData()->first();

            $columns = optional($row)->columns_data;

            if (blank($columns)) {
                ($quote->activeVersionOrCurrent)->detachColumnsFields();
                ($quote->activeVersionOrCurrent)->forgetCachedMappingReview();
                $quoteFile->markAsAutomapped();
                return;
            }

            $defaultAttributes = [
                'is_default_enabled' => false,
                'is_preview_visible' => true,
                'default_value' => null,
                'sort' => null,
            ];

            $importableColumns = ImportableColumn::whereKey($columns->pluck('importable_column_id'))->pluck('id', 'name');

            $map = $templateFields->pluck('id', 'name')
                ->mergeRecursive($importableColumns)
                ->filter(fn ($map) => is_array($map) && count($map) === 2)
                ->mapWithKeys(fn ($map, $key) => [
                    $map[0] => ['importable_column_id' => $map[1]] + $defaultAttributes
                ]);

            $quote->activeVersionOrCurrent->templateFields()->sync($map->toArray());

            $quote->activeVersionOrCurrent->forgetCachedMappingReview();

            $quoteFileLock = Cache::lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10);

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

        $lock = Cache::lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10);
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
