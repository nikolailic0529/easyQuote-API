<?php

namespace App\Services;

use App\Contracts\{
    Services\QuoteState,
    Services\ContractState,
    Repositories\QuoteFile\QuoteFileRepositoryInterface as QuoteFiles,
};
use App\Models\Quote\{
    Quote,
    Contract
};
use App\Repositories\Concerns\ResolvesImplicitModel;
use DB, Arr;

class ContractStateProcessor implements ContractState
{
    use ResolvesImplicitModel;

    const REG_CUSTOMER_RFQ_PREFIX = 'CQ';

    const QB_CUSTOMER_RFQ_PREFIX = 'CT';

    protected Contract $contract;

    protected QuoteState $quoteState;

    protected QuoteFiles $quoteFiles;

    public function __construct(Contract $contract, QuoteState $quoteState, QuoteFiles $quoteFiles)
    {
        $this->contract = $contract;
        $this->quoteFiles = $quoteFiles;
        $this->quoteState = $quoteState;
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

        DB::transaction(function () use ($contract, $state) {
            $contract->usingVersion->update($state);
        }, 3);

        return $contract;
    }

    public function createFromQuote(Quote $quote, array $attributes = [])
    {
        $contractAttributes = $attributes + Arr::except($quote->usingVersion->getAttributes(), ['additional_notes', 'closing_date']);

        return DB::transaction(function () use ($quote, $attributes, $contractAttributes) {
            $quote->update($attributes);

            /** We are updating relation attributes if the contract already exists. */
            if ($quote->contract()->exists()) {
                return tap($quote->contract)->update($attributes);
            }

            $version = $quote->usingVersion;

            $contract = tap($this->make($contractAttributes), function ($contract) use ($quote) {
                $contract->quote()->associate($quote);
                $contract->user()->associate(auth()->user());
                tap($contract)->unSubmit()->save();
            });

            $this->quoteState->replicateDiscounts($version->id, $contract->id);
            $this->quoteState->replicateMapping($version->id, $contract->id);

            $version->quoteFiles()->get()->each(function ($quoteFile) use ($contract) {
                switch ($quoteFile->file_type) {
                    case QFT_PL:
                        $contract->quoteFiles()->save($this->quoteFiles->replicatePriceList($quoteFile));
                        break;
                    case QFT_PS:
                        tap($quoteFile->replicate(['scheduleData']), function ($schedule) use ($contract, $quoteFile) {
                            $contract->quoteFiles()->save($schedule);
                            $schedule->scheduleData()->save($quoteFile->scheduleData->replicate());
                        });
                        break;
                }
            });

            return $contract;
        }, 3);
    }
}
