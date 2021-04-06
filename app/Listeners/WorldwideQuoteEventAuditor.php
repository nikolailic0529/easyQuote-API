<?php

namespace App\Listeners;

use App\Enum\ContractQuoteStage;
use App\Enum\PackQuoteStage;
use App\Events\WorldwideQuote\WorldwideContractQuoteDetailsStepProcessed;
use App\Events\WorldwideQuote\WorldwideContractQuoteDiscountStepProcessed;
use App\Events\WorldwideQuote\WorldwideContractQuoteImportStepProcessed;
use App\Events\WorldwideQuote\WorldwideContractQuoteMappingReviewStepProcessed;
use App\Events\WorldwideQuote\WorldwideContractQuoteMappingStepProcessed;
use App\Events\WorldwideQuote\WorldwidePackQuoteAssetsCreationStepProcessed;
use App\Events\WorldwideQuote\WorldwidePackQuoteAssetsReviewStepProcessed;
use App\Events\WorldwideQuote\WorldwidePackQuoteContactsStepProcessed;
use App\Events\WorldwideQuote\WorldwidePackQuoteDetailsStepProcessed;
use App\Events\WorldwideQuote\WorldwidePackQuoteDiscountStepProcessed;
use App\Events\WorldwideQuote\WorldwidePackQuoteMarginStepProcessed;
use App\Events\WorldwideQuote\WorldwideQuoteDeleted;
use App\Events\WorldwideQuote\WorldwideQuoteDrafted;
use App\Events\WorldwideQuote\WorldwideQuoteInitialized;
use App\Events\WorldwideQuote\WorldwideQuoteSubmitted;
use App\Events\WorldwideQuote\WorldwideQuoteUnraveled;
use App\Models\Company;
use App\Models\Data\Currency;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use App\Models\Quote\WorldwideQuote;
use App\Models\Template\QuoteTemplate;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\ChangesDetector;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

class WorldwideQuoteEventAuditor
{
    protected BusDispatcher $busDispatcher;

    protected ActivityLogger $activityLogger;

    protected ChangesDetector $changesDetector;

    public function __construct(BusDispatcher $busDispatcher, ActivityLogger $activityLogger, ChangesDetector $changesDetector)
    {
        $this->busDispatcher = $busDispatcher;
        $this->activityLogger = $activityLogger;
        $this->changesDetector = $changesDetector;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe(\Illuminate\Events\Dispatcher $events)
    {
        $events->listen(WorldwideQuoteInitialized::class, [$this, 'handleInitializedEvent']);
        $events->listen(WorldwideQuoteSubmitted::class, [$this, 'handleSubmittedEvent']);
        $events->listen(WorldwideQuoteUnraveled::class, [$this, 'handleUnraveledEvent']);
        $events->listen(WorldwideQuoteDrafted::class, [$this, 'handleDraftedEvent']);
        $events->listen(WorldwideQuoteDeleted::class, [$this, 'handleDeletedEvent']);

        $events->listen(WorldwideContractQuoteDetailsStepProcessed::class, [$this, 'handleContractQuoteDetailsStepProcessedEvent']);
        $events->listen(WorldwideContractQuoteDiscountStepProcessed::class, [$this, 'handleContractQuoteDiscountStepProcessedEvent']);
        $events->listen(WorldwideContractQuoteImportStepProcessed::class, [$this, 'handleContractQuoteImportStepProcessedEvent']);
        $events->listen(WorldwideContractQuoteMappingStepProcessed::class, [$this, 'handleContractQuoteMappingStepProcessedEvent']);
        $events->listen(WorldwideContractQuoteMappingReviewStepProcessed::class, [$this, 'handleContractQuoteMappingReviewStepProcessedEvent']);
        $events->listen(WorldwidePackQuoteAssetsCreationStepProcessed::class, [$this, 'handlePackQuoteAssetsCreationStepProcessedEvent']);
        $events->listen(WorldwidePackQuoteAssetsReviewStepProcessed::class, [$this, 'handlePackQuoteAssetsReviewStepProcessedEvent']);
        $events->listen(WorldwidePackQuoteContactsStepProcessed::class, [$this, 'handlePackQuoteContactsStepProcessedEvent']);
        $events->listen(WorldwidePackQuoteDetailsStepProcessed::class, [$this, 'handlePackQuoteDetailsStepProcessedEvent']);
        $events->listen(WorldwidePackQuoteDiscountStepProcessed::class, [$this, 'handlePackQuoteDiscountStepProcessedEvent']);
        $events->listen(WorldwidePackQuoteMarginStepProcessed::class, [$this, 'handlePackQuoteMarginStepProcessedEvent']);
    }

    private function getQuoteStage(WorldwideQuote $quote): ?string
    {
        if ($quote->contract_type_id === CT_PACK) {
            return PackQuoteStage::getLabelOfValue($quote->activeVersion->completeness);
        }

        if ($quote->contract_type_id === CT_CONTRACT) {
            return ContractQuoteStage::getLabelOfValue($quote->activeVersion->completeness);
        }

        return null;
    }

    public function handleInitializedEvent(WorldwideQuoteInitialized $event)
    {
        $quote = $event->getQuote();

        $this->activityLogger
            ->performedOn($quote)
            ->withProperties([
                'old' => [],
                'attributes' => [
                    'contract_type' => $quote->contractType->type_short_name,
                    'project_name' => $quote->opportunity->project_name,
                    'stage' => $this->getQuoteStage($quote),
                    'quote_number' => $quote->quote_number
                ]
            ])
            ->log('created');
    }

    public function handleSubmittedEvent(WorldwideQuoteSubmitted $event)
    {
        $quote = $event->getQuote();
        $oldQuote = $event->getOldQuote();

        $this->activityLogger
            ->performedOn($quote)
            ->withProperties([
                'old' => [
                    'submitted_at' => $oldQuote->submitted_at
                ],
                'attributes' => [
                    'submitted_at' => $quote->submitted_at
                ]
            ])
            ->log('submitted');
    }

    public function handleUnraveledEvent(WorldwideQuoteUnraveled $event)
    {
        $quote = $event->getQuote();

        $this->activityLogger
            ->performedOn($quote)
            ->withProperties([
                'old' => [
                    'submitted_at' => $quote->submitted_at
                ],
                'attributes' => [
                    'submitted_at' => null
                ]
            ])
            ->log('unravel');
    }

    public function handleDraftedEvent(WorldwideQuoteDrafted $event)
    {
        $quote = $event->getQuote();
        $oldQuote = $event->getOldQuote();

        $this->activityLogger
            ->performedOn($quote)
            ->withProperties([
                'old' => [
                    'closing_date' => $oldQuote->activeVersion->closing_date,
                    'additional_notes' => $oldQuote->activeVersion->additional_notes
                ],
                'attributes' => [
                    'closing_date' => $quote->activeVersion->closing_date,
                    'additional_notes' => $quote->activeVersion->additional_notes
                ]
            ])
            ->log('updated');
    }

    public function handleDeletedEvent(WorldwideQuoteDeleted $event)
    {
        $quote = $event->getQuote();

        $this->activityLogger
            ->performedOn($quote)
            ->withProperties([
                'old' => [],
                'attributes' => []
            ])
            ->log('deleted');
    }

    public function handleContractQuoteDetailsStepProcessedEvent(WorldwideContractQuoteDetailsStepProcessed $event)
    {
        $quote = $event->getQuote();

        $this->activityLogger
            ->performedOn($quote)
            ->withProperties([
                'old' => [],
                'attributes' => []
            ])
            ->log('deleted');
    }

    public function handleContractQuoteDiscountStepProcessedEvent(WorldwideContractQuoteDiscountStepProcessed $event)
    {
        $quote = $event->getQuote();
        $oldQuote = $event->getOldQuote();

//        $this->activityLogger
//            ->performedOn($quote)
//            ->withProperties([
//                'old' => [],
//                'attributes' => []
//            ])
//            ->log('updated');
    }

    public function handleContractQuoteImportStepProcessedEvent(WorldwideContractQuoteImportStepProcessed $event)
    {
        $quote = $event->getQuote();
        $oldQuote = $event->getOldQuote();

//        $this->activityLogger
//            ->performedOn($quote)
//            ->withProperties([
//                'old' => [],
//                'attributes' => []
//            ])
//            ->log('updated');
    }

    public function handleContractQuoteMappingStepProcessedEvent(WorldwideContractQuoteMappingStepProcessed $event)
    {
        $quote = $event->getQuote();
        $oldQuote = $event->getOldQuote();

        $this->activityLogger
            ->performedOn($quote)
            ->withProperties([
                'old' => [],
                'attributes' => []
            ])
            ->log('updated');
    }

    public function handleContractQuoteMappingReviewStepProcessedEvent(WorldwideContractQuoteMappingReviewStepProcessed $event)
    {
        $quote = $event->getQuote();
        $oldQuote = $event->getOldQuote();

//        $this->activityLogger
//            ->performedOn($quote)
//            ->withProperties([
//                'old' => [],
//                'attributes' => []
//            ])
//            ->log('updated');
    }

    public function handlePackQuoteAssetsCreationStepProcessedEvent(WorldwidePackQuoteAssetsCreationStepProcessed $event)
    {
        $quote = $event->getQuote();
        $oldQuote = $event->getOldQuote();

//        $this->activityLogger
//            ->performedOn($quote)
////            ->causedBy($quote->activeVersion->user)
//            ->withProperties([
//                'old' => [],
//                'attributes' => []
//            ])
//            ->log('updated');
    }

    public function handlePackQuoteAssetsReviewStepProcessedEvent(WorldwidePackQuoteAssetsReviewStepProcessed $event)
    {
        $quote = $event->getQuote();
        $oldQuote = $event->getOldQuote();

        $this->activityLogger
            ->performedOn($quote)
//            ->causedBy($quote->activeVersion->user)
            ->withProperties([
                'old' => [
                    'sort_rows_column' => $oldQuote->activeVersion->sort_rows_column,
                    'sort_rows_direction' => $oldQuote->activeVersion->sort_rows_direction,
                    // TODO: add selected rows count
                    'selected_rows_count' => $oldQuote->activeVersion->selected_rows_count,
                ],
                'attributes' => [
                    'sort_rows_column' => $quote->activeVersion->sort_rows_column,
                    'sort_rows_direction' => $quote->activeVersion->sort_rows_direction,
                    // TODO: add selected rows count
                    'selected_rows_count' => $quote->activeVersion->selected_rows_count,
                ]
            ])
            ->log('updated');
    }

    public function handlePackQuoteContactsStepProcessedEvent(WorldwidePackQuoteContactsStepProcessed $event)
    {
        $quote = $event->getQuote();
        $oldQuote = $event->getOldQuote();

        $this->activityLogger
            ->performedOn($quote)
//            ->causedBy($quote->activeVersion->user)
            ->withProperties([
                'old' => [
                    'company' => transform($oldQuote->activeVersion->company, fn(Company $company) => $company->name),
                    'quote_currency' => transform($oldQuote->activeVersion->quoteCurrency, fn(Currency $currency) => $currency->code),
                    'quote_template' => transform($oldQuote->activeVersion->quoteTemplate, fn(QuoteTemplate $template) => $template->name),
                    'buy_price' => $oldQuote->activeVersion->buy_price,
                    'quote_expiry_date' => $oldQuote->activeVersion->quote_expiry_date,
                    'payment_terms' => $oldQuote->activeVersion->payment_terms,
                ],
                'attributes' => [
                    'company' => transform($quote->activeVersion->company, fn(Company $company) => $company->name),
                    'quote_currency' => transform($quote->activeVersion->quoteCurrency, fn(Currency $currency) => $currency->code),
                    'quote_template' => transform($quote->activeVersion->quoteTemplate, fn(QuoteTemplate $template) => $template->name),
                    'buy_price' => $quote->activeVersion->buy_price,
                    'quote_expiry_date' => $quote->activeVersion->quote_expiry_date,
                    'payment_terms' => $quote->activeVersion->payment_terms,
                ]
            ])
            ->log('updated');
    }

    public function handlePackQuoteDetailsStepProcessedEvent(WorldwidePackQuoteDetailsStepProcessed $event)
    {
        $quote = $event->getQuote();
        $oldQuote = $event->getOldQuote();

        $this->activityLogger
            ->performedOn($quote)
//            ->causedBy($quote->activeVersion->user)
            ->withProperties([
                'old' => [
                    'pricing_document' => $oldQuote->activeVersion->pricing_document,
                    'service_agreement_id' => $oldQuote->activeVersion->service_agreement_id,
                    'system_handle' => $oldQuote->activeVersion->system_handle,
                    'additional_details' => $oldQuote->activeVersion->additional_details,
                ],
                'attributes' => [
                    'pricing_document' => $quote->activeVersion->pricing_document,
                    'service_agreement_id' => $quote->activeVersion->service_agreement_id,
                    'system_handle' => $quote->activeVersion->system_handle,
                    'additional_details' => $quote->activeVersion->additional_details,
                ]
            ])
            ->log('updated');
    }

    public function handlePackQuoteDiscountStepProcessedEvent(WorldwidePackQuoteDiscountStepProcessed $event)
    {
        $quote = $event->getQuote();
        $oldQuote = $event->getOldQuote();

        $this->activityLogger
            ->performedOn($quote)
//            ->causedBy($quote->activeVersion->user)
            ->withProperties([
                'old' => [
                    'multi_year_discount' => transform($oldQuote->activeVersion->multiYearDiscount, fn(MultiYearDiscount $discount) => $discount->name),
                    'pre_pay_discount' => transform($oldQuote->activeVersion->prePayDiscount, fn(PrePayDiscount $discount) => $discount->name),
                    'promotional_discount' => transform($oldQuote->activeVersion->promotionalDiscount, fn(PromotionalDiscount $discount) => $discount->name),
                    'sn_discount' => transform($oldQuote->activeVersion->snDiscount, fn(SND $discount) => $discount->name),
                    'custom_discount' => number_format((float)$oldQuote->activeVersion->custom_discount)
                ],
                'attributes' => [
                    'multi_year_discount' => transform($quote->activeVersion->multiYearDiscount, fn(MultiYearDiscount $discount) => $discount->name),
                    'pre_pay_discount' => transform($quote->activeVersion->prePayDiscount, fn(PrePayDiscount $discount) => $discount->name),
                    'promotional_discount' => transform($quote->activeVersion->promotionalDiscount, fn(PromotionalDiscount $discount) => $discount->name),
                    'sn_discount' => transform($quote->activeVersion->snDiscount, fn(SND $discount) => $discount->name),
                    'custom_discount' => number_format((float)$quote->activeVersion->custom_discount)
                ]
            ])
            ->log('updated');
    }

    public function handlePackQuoteMarginStepProcessedEvent(WorldwidePackQuoteMarginStepProcessed $event)
    {
        $quote = $event->getQuote();
        $oldQuote = $event->getOldQuote();

        $this->activityLogger
            ->performedOn($quote)
//            ->causedBy($quote->activeVersion->user)
            ->withProperties([
                'old' => [
                    'quote_type' => $oldQuote->activeVersion->quote_type,
                    'margin_value' => $oldQuote->activeVersion->margin_value,
                    'margin_method' => $oldQuote->activeVersion->margin_method,
                    'tax_value' => $oldQuote->activeVersion->tax_value,
                ],
                'attributes' => [
                    'quote_type' => $quote->activeVersion->quote_type,
                    'margin_value' => $quote->activeVersion->margin_value,
                    'margin_method' => $quote->activeVersion->margin_method,
                    'tax_value' => $quote->activeVersion->tax_value,
                ]
            ])
            ->log('updated');
    }
}
