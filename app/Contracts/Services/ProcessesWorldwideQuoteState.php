<?php

namespace App\Contracts\Services;

use App\DTO\AssetsGroupData;
use App\DTO\DistributionDetailsCollection;
use App\DTO\ProcessableDistributionCollection;
use App\DTO\QuoteStages\QuoteSetupStage;
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
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\User;
use App\Models\WorldwideQuoteAssetsGroup;

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
     * Switch active version of Worldwide Quote.
     *
     * @param WorldwideQuote $quote
     * @param WorldwideQuoteVersion $version
     */
    public function switchActiveVersionOfQuote(WorldwideQuote $quote, WorldwideQuoteVersion $version): void;

    /**
     * Delete the specified version of Worldwide Quote.
     *
     * @param WorldwideQuote $quote
     * @param WorldwideQuoteVersion $version
     */
    public function deleteVersionOfQuote(WorldwideQuote $quote, WorldwideQuoteVersion $version): void;

    /**
     * Synchronize Contract Quote with Opportunity Data.
     *
     * @param WorldwideQuote $quote
     * @return void
     */
    public function syncQuoteWithOpportunityData(WorldwideQuote $quote): void;

    /**
     * Process import of distributor quote files.
     *
     * @param \App\Models\Quote\WorldwideQuoteVersion $version
     * @param \App\DTO\ProcessableDistributionCollection $collection
     */
    public function processImportOfDistributorQuotes(WorldwideQuoteVersion $version, ProcessableDistributionCollection $collection): void;

    /**
     * Process Quote import step.
     *
     * @param WorldwideQuoteVersion $quoteVersion
     * @param \App\DTO\QuoteStages\ImportStage $stage
     * @return \App\Models\Quote\WorldwideQuoteVersion
     */
    public function processQuoteImportStep(WorldwideQuoteVersion $quoteVersion, ImportStage $stage): WorldwideQuoteVersion;

    /**
     * Process Pack Quote margin step.
     *
     * @param WorldwideQuoteVersion $quote
     * @param PackMarginTaxStage $stage
     * @return WorldwideQuoteVersion
     */
    public function processPackQuoteMarginStep(WorldwideQuoteVersion $quote, PackMarginTaxStage $stage): WorldwideQuoteVersion;

    /**
     * Process Pack Quote discount step.
     *
     * @param WorldwideQuoteVersion $quote
     * @param PackDiscountStage $stage
     * @return WorldwideQuoteVersion
     */
    public function processPackQuoteDiscountStep(WorldwideQuoteVersion $quote, PackDiscountStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote addresses & contacts step.
     *
     * @param WorldwideQuoteVersion $quote
     * @param QuoteSetupStage $stage
     * @return WorldwideQuoteVersion
     */
    public function processQuoteSetupStep(WorldwideQuoteVersion $quote, QuoteSetupStage $stage): WorldwideQuoteVersion;

    /**
     * Process Pack Quote details step.
     *
     * @param WorldwideQuoteVersion $quote
     * @param PackDetailsStage $stage
     * @return WorldwideQuoteVersion
     */
    public function processPackQuoteDetailsStep(WorldwideQuoteVersion $quote, PackDetailsStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote assets creation step.
     *
     * @param WorldwideQuoteVersion $quote
     * @param PackAssetsCreationStage $stage
     * @return WorldwideQuoteVersion
     */
    public function processQuoteAssetsCreationStep(WorldwideQuoteVersion $quote, PackAssetsCreationStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote assets review step.
     *
     * @param WorldwideQuoteVersion $quote
     * @param PackAssetsReviewStage $stage
     * @return WorldwideQuoteVersion
     */
    public function processQuoteAssetsReviewStep(WorldwideQuoteVersion $quote, PackAssetsReviewStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote mapping step.
     *
     * @param WorldwideQuoteVersion $version
     * @param \App\DTO\QuoteStages\MappingStage $stage
     * @return \App\Models\Quote\WorldwideQuote
     */
    public function processQuoteMappingStep(WorldwideQuoteVersion $version, MappingStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote mapping review step.
     *
     * @param WorldwideQuoteVersion $quote
     * @param \App\DTO\QuoteStages\ReviewStage $stage
     * @return WorldwideQuoteVersion
     */
    public function processQuoteMappingReviewStep(WorldwideQuoteVersion $quote, ReviewStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote margin step.
     *
     * @param WorldwideQuoteVersion $quote
     * @param \App\DTO\QuoteStages\ContractMarginTaxStage $stage
     * @return \App\Models\Quote\WorldwideQuote
     */
    public function processQuoteMarginStep(WorldwideQuoteVersion $quote, ContractMarginTaxStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote discount step.
     *
     * @param WorldwideQuoteVersion $quote
     * @param ContractDiscountStage $stage
     * @return WorldwideQuote
     */
    public function processQuoteDiscountStep(WorldwideQuoteVersion $quote, ContractDiscountStage $stage): WorldwideQuoteVersion;

    /**
     * Process Contract Quote details step.
     *
     * @param WorldwideQuoteVersion $quote
     * @param ContractDetailsStage $stage
     * @return WorldwideQuoteVersion
     */
    public function processContractQuoteDetailsStep(WorldwideQuoteVersion $quote, ContractDetailsStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote submission.
     *
     * @param WorldwideQuoteVersion $quote
     * @param SubmitStage $stage
     * @return WorldwideQuoteVersion
     */
    public function processQuoteSubmission(WorldwideQuoteVersion $quote, SubmitStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote draft.
     *
     * @param WorldwideQuoteVersion $quote
     * @param DraftStage $stage
     * @return WorldwideQuoteVersion
     */
    public function processQuoteDraft(WorldwideQuoteVersion $quote, DraftStage $stage): WorldwideQuoteVersion;

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

    /**
     * Process Quote replication.
     *
     * @param WorldwideQuote $quote
     * @param User $actingUser
     * @return WorldwideQuote
     */
    public function processQuoteReplication(WorldwideQuote $quote, User $actingUser): WorldwideQuote;

    /**
     * Set acting user entity.
     *
     * @param \App\Models\User|null $user
     * @return $this
     */
    public function setActingUser(User $user = null): self;

    /**
     * Create a new group of pack quote assets.
     *
     * @param \App\Models\Quote\WorldwideQuoteVersion $quote
     * @param \App\DTO\AssetsGroupData $data
     * @return \App\Models\WorldwideQuoteAssetsGroup
     */
    public function createGroupOfAssets(WorldwideQuoteVersion $quote, AssetsGroupData $data): WorldwideQuoteAssetsGroup;

    /**
     * Update the group of pack quote assets.
     *
     * @param \App\Models\Quote\WorldwideQuoteVersion $quote
     * @param \App\Models\WorldwideQuoteAssetsGroup $assetsGroup
     * @param \App\DTO\AssetsGroupData $data
     * @return \App\Models\WorldwideQuoteAssetsGroup
     */
    public function updateGroupOfAssets(WorldwideQuoteVersion $quote, WorldwideQuoteAssetsGroup $assetsGroup, AssetsGroupData $data): WorldwideQuoteAssetsGroup;

    /**
     * Delete the group of pack quote assets.
     *
     * @param \App\Models\Quote\WorldwideQuoteVersion $quote
     * @param \App\Models\WorldwideQuoteAssetsGroup $assetsGroup
     */
    public function deleteGroupOfAssets(WorldwideQuoteVersion $quote, WorldwideQuoteAssetsGroup $assetsGroup): void;

    /**
     * Move assets between the groups of pack quote assets.
     *
     * @param \App\Models\Quote\WorldwideQuoteVersion $quote
     * @param \App\Models\WorldwideQuoteAssetsGroup $outputAssetsGroup
     * @param \App\Models\WorldwideQuoteAssetsGroup $inputAssetsGroup
     * @param array $assets
     */
    public function moveAssetsBetweenGroupsOfAssets(WorldwideQuoteVersion $quote, WorldwideQuoteAssetsGroup $outputAssetsGroup, WorldwideQuoteAssetsGroup $inputAssetsGroup, array $assets): void;
}
