<?php

namespace App\Contracts\Services;

use App\DTO\QuoteStages\AddressesContactsStage;
use App\DTO\QuoteStages\PackAssetsCreationStage;
use App\DTO\QuoteStages\ContractDetailsStage;
use App\DTO\QuoteStages\ContractDiscountStage;
use App\DTO\QuoteStages\DraftStage;
use App\DTO\QuoteStages\ImportStage;
use App\DTO\QuoteStages\InitStage;
use App\DTO\QuoteStages\MappingStage;
use App\DTO\QuoteStages\ContractMarginTaxStage;
use App\DTO\QuoteStages\PackAssetsReviewStage;
use App\DTO\QuoteStages\PackDetailsStage;
use App\DTO\QuoteStages\PackDiscountStage;
use App\DTO\QuoteStages\PackMarginTaxStage;
use App\DTO\QuoteStages\ReviewStage;
use App\DTO\QuoteStages\SubmitStage;
use App\DTO\WorldwideQuote\MarkWorldwideQuoteAsDeadData;
use App\Models\Opportunity;
use App\Models\Quote\WorldwideQuote;

interface ProcessesWorldwideQuoteState
{
    /**
     * Initialize a new Worldwide Quote for the specific Customer.
     *
     * @param \App\DTO\QuoteStages\InitStage $stage
     * @return \App\Models\Quote\WorldwideQuote
     */
    public function initializeQuote(InitStage $stage): WorldwideQuote;

    /**
     * Synchronize Contract Quote with Opportunity Data.
     *
     * @param WorldwideQuote $quote
     * @return void
     */
    public function syncContractQuoteWithOpportunityData(WorldwideQuote $quote): void;

    /**
     * Process Quote import step.
     *
     * @param \App\Models\Quote\WorldwideQuote $quote
     * @param \App\DTO\QuoteStages\ImportStage $stage
     * @return \App\Models\Quote\WorldwideQuote
     */
    public function processQuoteImportStep(WorldwideQuote $quote, ImportStage $stage): WorldwideQuote;

    /**
     * Process Pack Quote margin step.
     *
     * @param WorldwideQuote $quote
     * @param PackMarginTaxStage $stage
     * @return WorldwideQuote
     */
    public function processPackQuoteMarginStep(WorldwideQuote $quote, PackMarginTaxStage $stage): WorldwideQuote;

    /**
     * Process Pack Quote discount step.
     *
     * @param WorldwideQuote $quote
     * @param PackDiscountStage $stage
     * @return WorldwideQuote
     */
    public function processPackQuoteDiscountStep(WorldwideQuote $quote, PackDiscountStage $stage): WorldwideQuote;

    /**
     * Process Quote addresses & contacts step.
     *
     * @param WorldwideQuote $quote
     * @param AddressesContactsStage $stage
     * @return WorldwideQuote
     */
    public function processQuoteAddressesContactsStep(WorldwideQuote $quote, AddressesContactsStage $stage): WorldwideQuote;

    /**
     * Process Pack Quote details step.
     *
     * @param WorldwideQuote $quote
     * @param PackDetailsStage $stage
     * @return WorldwideQuote
     */
    public function processPackQuoteDetailsStep(WorldwideQuote $quote, PackDetailsStage $stage): WorldwideQuote;

    /**
     * Process Quote assets creation step.
     *
     * @param WorldwideQuote $quote
     * @param PackAssetsCreationStage $stage
     * @return WorldwideQuote
     */
    public function processQuoteAssetsCreationStep(WorldwideQuote $quote, PackAssetsCreationStage $stage): WorldwideQuote;

    /**
     * Process Quote assets review step.
     *
     * @param WorldwideQuote $quote
     * @param PackAssetsReviewStage $stage
     * @return WorldwideQuote
     */
    public function processQuoteAssetsReviewStep(WorldwideQuote $quote, PackAssetsReviewStage $stage): WorldwideQuote;

    /**
     * Process Quote mapping step.
     *
     * @param \App\DTO\QuoteStages\MappingStage $stage
     * @return \App\Models\Quote\WorldwideQuote
     */
    public function processQuoteMappingStep(MappingStage $stage): WorldwideQuote;

    /**
     * Process Quote mapping review step.
     *
     * @param \App\DTO\QuoteStages\ReviewStage $stage
     * @return \App\Models\Quote\WorldwideQuote
     */
    public function processQuoteMappingReviewStep(ReviewStage $stage): WorldwideQuote;

    /**
     * Process Quote margin step.
     *
     * @param \App\DTO\QuoteStages\ContractMarginTaxStage $stage
     * @return \App\Models\Quote\WorldwideQuote
     */
    public function processQuoteMarginStep(ContractMarginTaxStage $stage): WorldwideQuote;

    /**
     * Process Quote discount step.
     *
     * @param WorldwideQuote $quote
     * @param ContractDiscountStage $stage
     * @return WorldwideQuote
     */
    public function processQuoteDiscountStep(WorldwideQuote $quote, ContractDiscountStage $stage): WorldwideQuote;

    /**
     * Process Contract Quote details step.
     *
     * @param WorldwideQuote $quote
     * @param ContractDetailsStage $stage
     * @return WorldwideQuote
     */
    public function processContractQuoteDetailsStep(WorldwideQuote $quote, ContractDetailsStage $stage): WorldwideQuote;

    /**
     * Process Quote submission.
     *
     * @param WorldwideQuote $quote
     * @param SubmitStage $stage
     * @return mixed
     */
    public function processQuoteSubmission(WorldwideQuote $quote, SubmitStage $stage): WorldwideQuote;

    /**
     * Process Quote draft.
     *
     * @param WorldwideQuote $quote
     * @param DraftStage $stage
     * @return WorldwideQuote
     */
    public function processQuoteDraft(WorldwideQuote $quote, DraftStage $stage): WorldwideQuote;

    /**
     * Process Quote unravel.
     *
     * @param WorldwideQuote $quote
     * @return mixed
     */
    public function processQuoteUnravel(WorldwideQuote $quote): WorldwideQuote;

    /**
     * Mark Quote as active.
     *
     * @param WorldwideQuote $quote
     */
    public function activateQuote(WorldwideQuote $quote): void;

    /**
     * Mark Quote as inactive.
     *
     * @param WorldwideQuote $quote
     */
    public function deactivateQuote(WorldwideQuote $quote): void;

    /**
     * Perform deleting the Quote & relationships.
     *
     * @param \App\Models\Quote\WorldwideQuote $quote
     * @return void
     */
    public function deleteQuote(WorldwideQuote $quote): void;

    /**
     * Mark worldwide quote as 'dead'.
     *
     * @param WorldwideQuote $quote
     * @param MarkWorldwideQuoteAsDeadData $data
     */
    public function markQuoteAsDead(WorldwideQuote $quote, MarkWorldwideQuoteAsDeadData $data): void;

    /**
     * Mark worldwide quote as 'alive'.
     *
     * @param WorldwideQuote $quote
     */
    public function markQuoteAsAlive(WorldwideQuote $quote): void;
}
