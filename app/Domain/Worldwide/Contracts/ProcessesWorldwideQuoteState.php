<?php

namespace App\Domain\Worldwide\Contracts;

use App\Domain\Asset\DataTransferObjects\AssetsGroupData;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\DataTransferObjects\DistributorQuote\ProcessableDistributionCollection;
use App\Domain\Worldwide\DataTransferObjects\Quote\MarkWorldwideQuoteAsDeadData;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\ContractDetailsStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\ContractDiscountStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\ContractMarginTaxStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\DraftStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\ImportStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\InitStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\MappingStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\PackAssetsCreationStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\PackAssetsReviewStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\PackDetailsStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\PackDiscountStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\PackMarginTaxStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\QuoteSetupStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\ReviewStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\SubmitStage;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteAssetsGroup;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;

interface ProcessesWorldwideQuoteState
{
    /**
     * Initialize a new Worldwide Quote for the specific Customer.
     */
    public function initializeQuote(InitStage $stage): WorldwideQuote;

    /**
     * Switch active version of Worldwide Quote.
     */
    public function switchActiveVersionOfQuote(WorldwideQuote $quote, WorldwideQuoteVersion $version): void;

    /**
     * Delete the specified version of Worldwide Quote.
     */
    public function deleteVersionOfQuote(WorldwideQuote $quote, WorldwideQuoteVersion $version): void;

    /**
     * Synchronize Contract Quote with Opportunity Data.
     */
    public function syncQuoteWithOpportunityData(WorldwideQuote $quote): void;

    /**
     * Process import of distributor quote files.
     */
    public function processImportOfDistributorQuotes(WorldwideQuoteVersion $version, ProcessableDistributionCollection $collection): void;

    /**
     * Process Quote import step.
     */
    public function processQuoteImportStep(WorldwideQuoteVersion $quoteVersion, ImportStage $stage): WorldwideQuoteVersion;

    /**
     * Process Pack Quote margin step.
     */
    public function processPackQuoteMarginStep(WorldwideQuoteVersion $quote, PackMarginTaxStage $stage): WorldwideQuoteVersion;

    /**
     * Process Pack Quote discount step.
     */
    public function processPackQuoteDiscountStep(WorldwideQuoteVersion $quote, PackDiscountStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote addresses & contacts step.
     */
    public function processQuoteSetupStep(WorldwideQuoteVersion $quote, QuoteSetupStage $stage): WorldwideQuoteVersion;

    /**
     * Process Pack Quote details step.
     */
    public function processPackQuoteDetailsStep(WorldwideQuoteVersion $quote, PackDetailsStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote assets creation step.
     */
    public function processQuoteAssetsCreationStep(WorldwideQuoteVersion $quote, PackAssetsCreationStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote assets review step.
     */
    public function processQuoteAssetsReviewStep(WorldwideQuoteVersion $quote, PackAssetsReviewStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote mapping step.
     *
     * @return \App\Domain\Worldwide\Models\WorldwideQuote
     */
    public function processQuoteMappingStep(WorldwideQuoteVersion $version, MappingStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote mapping review step.
     */
    public function processQuoteMappingReviewStep(WorldwideQuoteVersion $quote, ReviewStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote margin step.
     *
     * @return \App\Domain\Worldwide\Models\WorldwideQuote
     */
    public function processQuoteMarginStep(WorldwideQuoteVersion $quote, ContractMarginTaxStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote discount step.
     *
     * @return \App\Domain\Worldwide\Models\WorldwideQuote
     */
    public function processQuoteDiscountStep(WorldwideQuoteVersion $quote, ContractDiscountStage $stage): WorldwideQuoteVersion;

    /**
     * Process Contract Quote details step.
     */
    public function processContractQuoteDetailsStep(WorldwideQuoteVersion $quote, ContractDetailsStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote submission.
     */
    public function processQuoteSubmission(WorldwideQuoteVersion $quote, SubmitStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote draft.
     */
    public function processQuoteDraft(WorldwideQuoteVersion $quote, DraftStage $stage): WorldwideQuoteVersion;

    /**
     * Process Quote unravel.
     *
     * @return mixed
     */
    public function processQuoteUnravel(WorldwideQuote $quote): WorldwideQuote;

    /**
     * Mark Quote as active.
     */
    public function activateQuote(WorldwideQuote $quote): void;

    /**
     * Mark Quote as inactive.
     */
    public function deactivateQuote(WorldwideQuote $quote): void;

    /**
     * Perform deleting the Quote & relationships.
     */
    public function deleteQuote(WorldwideQuote $quote): void;

    /**
     * Mark worldwide quote as 'dead'.
     */
    public function markQuoteAsDead(WorldwideQuote $quote, MarkWorldwideQuoteAsDeadData $data): void;

    /**
     * Mark worldwide quote as 'alive'.
     */
    public function markQuoteAsAlive(WorldwideQuote $quote): void;

    /**
     * Process Quote replication.
     */
    public function processQuoteReplication(WorldwideQuote $quote, User $actingUser): WorldwideQuote;

    /**
     * Set acting user entity.
     *
     * @return $this
     */
    public function setActingUser(User $user = null): self;

    /**
     * Create a new group of pack quote assets.
     */
    public function createGroupOfAssets(WorldwideQuoteVersion $quote, AssetsGroupData $data): WorldwideQuoteAssetsGroup;

    /**
     * Update the group of pack quote assets.
     */
    public function updateGroupOfAssets(WorldwideQuoteVersion $quote, WorldwideQuoteAssetsGroup $assetsGroup, AssetsGroupData $data): WorldwideQuoteAssetsGroup;

    /**
     * Delete the group of pack quote assets.
     */
    public function deleteGroupOfAssets(WorldwideQuoteVersion $quote, WorldwideQuoteAssetsGroup $assetsGroup): void;

    /**
     * Move assets between the groups of pack quote assets.
     */
    public function moveAssetsBetweenGroupsOfAssets(WorldwideQuoteVersion $quote, WorldwideQuoteAssetsGroup $outputAssetsGroup, WorldwideQuoteAssetsGroup $inputAssetsGroup, array $assets): void;
}
