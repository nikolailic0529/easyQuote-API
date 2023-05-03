<?php

namespace App\Domain\Rescue\Services;

use App\Domain\QuoteFile\Contracts\QuoteFileRepositoryInterface as QuoteFiles;
use App\Domain\Rescue\DataTransferObjects\RowsGroup;
use App\Domain\Rescue\Models\{QuoteVersion};
use App\Domain\Rescue\Models\BaseQuote;
use App\Domain\Rescue\Models\Contract;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Shared\Eloquent\Repository\Concerns\ResolvesImplicitModel;
use App\Domain\Sync\Enum\Lock;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractStateProcessor implements \App\Domain\Rescue\Contracts\ContractState
{
    use ResolvesImplicitModel;

    const REG_CUSTOMER_RFQ_PREFIX = 'CQ';

    const QB_CUSTOMER_RFQ_PREFIX = 'CT';

    protected Contract $contract;

    protected \App\Domain\Rescue\Contracts\QuoteState $quoteState;

    protected QuoteFiles $quoteFiles;

    public function __construct(Contract $contract, QuoteFiles $quoteFiles)
    {
        $this->contract = $contract;
        $this->quoteFiles = $quoteFiles;
    }

    public function find(string $id)
    {
        return $this->contract->query()->whereId($id)->first();
    }

    public function model(): string
    {
        return Contract::class;
    }

    public function make(array $attributes = []): Contract
    {
        return $this->contract->make($attributes);
    }

    public function storeState(array $state, $contract)
    {
        $contract = $this->resolveModel($contract);

        $lock = Cache::lock(Lock::UPDATE_CONTRACT($contract->getKey()), 10);

        $lock->block(30, fn () => $contract->update($state));

        return $contract;
    }

    public function replicateMappingFromQuote(BaseQuote $source, Contract $target)
    {
        $sourceTable = $source instanceof QuoteVersion ? 'quote_version_field_column' : 'quote_field_column';
        $sourceForeignKeyName = $source instanceof QuoteVersion ? 'quote_version_id' : 'quote_id';

        $targetTable = 'contract_field_column';
        $targetForeignKeyName = 'contract_id';

        DB::table($targetTable)->insertUsing(
            [$targetForeignKeyName, 'template_field_id', 'importable_column_id', 'is_default_enabled', 'sort'],
            DB::table($sourceTable)->select(
                DB::raw("'{$target->getKey()}' as $targetForeignKeyName"),
                'template_field_id',
                'importable_column_id',
                'is_default_enabled',
                'sort'
            )
                ->where($sourceForeignKeyName, $source->getKey())
        );
    }

    protected function mapQuoteAttributesToContract(BaseQuote $quote)
    {
        $attributes = $quote->getAttributes();

        return [
            'id' => Arr::get($attributes, 'id'),
            'user_id' => Arr::get($attributes, 'user_id'),
            'contract_template_id' => Arr::get($attributes, 'contract_template_id'),
            'company_id' => Arr::get($attributes, 'company_id'),
            'vendor_id' => Arr::get($attributes, 'vendor_id'),
            'country_id' => Arr::get($attributes, 'country_id'),
            'customer_id' => Arr::get($attributes, 'customer_id'),
            'schedule_file_id' => Arr::get($attributes, 'schedule_file_id'),
            'distributor_file_id' => Arr::get($attributes, 'distributor_file_id'),
            'previous_state' => Arr::get($attributes, 'previous_state'),
            'completeness' => Arr::get($attributes, 'completeness'),
            'pricing_document' => Arr::get($attributes, 'pricing_document'),
            'service_agreement_id' => Arr::get($attributes, 'service_agreement_id'),
            'system_handle' => Arr::get($attributes, 'system_handle'),
            'contract_date' => Arr::get($attributes, 'closing_date'),
            'additional_notes' => Arr::get($attributes, 'additional_notes'),
            'group_description' => Arr::get($attributes, 'group_description'),
            'use_groups' => Arr::get($attributes, 'use_groups'),
            'sort_group_description' => Arr::get($attributes, 'sort_group_description'),
            'customer_name' => $quote->customer->name,
            'contract_number' => Str::replaceFirst('CQ', 'CT', $quote->customer->rfq),
        ];
    }

    public function createFromQuote(Quote $quote, array $attributes = [])
    {
        $contractAttributes = $attributes + $this->mapQuoteAttributesToContract($quote->activeVersionOrCurrent);

        $lock = Cache::lock(Lock::UPDATE_QUOTE($quote->getKey()), 10);

        $lock->block(30);

        $quote->update($attributes);

        /* We are updating relation attributes if the contract already exists. */
        if ($quote->contract()->exists()) {
            $contractLock = Cache::lock(Lock::UPDATE_CONTRACT($quote->contract->getKey()), 10);

            return $contractLock->block(30, fn () => tap($quote->contract)->update($attributes));
        }

        $version = $quote->activeVersionOrCurrent;

        $contract = tap($this->make($contractAttributes), function ($contract) use ($quote) {
            $contract->quote()->associate($quote);
            $contract->user()->associate(auth()->user());

            $contractLock = Cache::lock(Lock::CREATE_CONTRACT($quote->getKey()), 10);

            $contractLock->block(30, function () use ($contract) {
                $contract->unSubmit();
                $contract->save();
            });
        });

        $this->replicateMappingFromQuote($version, $contract);

        if ($version->priceList->exists) {
            $priceListFile = $this->quoteFiles->replicatePriceList($version->priceList);

            $contract->distributor_file_id = $priceListFile->getKey();
        }

        if ($version->paymentSchedule->exists) {
            tap($version->paymentSchedule->replicate(), function ($schedule) use ($contract, $version) {
                $schedule->save();
                $schedule->scheduleData()->save($version->paymentSchedule->scheduleData->replicate());
                $contract->schedule_file_id = $schedule->getKey();
            });
        }

        $contract->save();

        if ($version->group_description->isNotEmpty()) {
            $rowsIds = $contract->groupedRows();

            /** @var \Illuminate\Database\Eloquent\Collection */
            $replicatedRows = $contract->rowsData()->getQuery()->toBase()->whereIn('imported_rows.replicated_row_id', $rowsIds)->get(['imported_rows.id', 'imported_rows.replicated_row_id']);

            $contract->group_description->each(function (RowsGroup $group) use ($replicatedRows) {
                $rowsIds = $replicatedRows->whereIn('replicated_row_id', $group->rows_ids)->pluck('id');

                $group->rows_ids = $rowsIds->toArray();
            });

            $contract->save();
        }

        $lock->release();

        return $contract;
    }
}
