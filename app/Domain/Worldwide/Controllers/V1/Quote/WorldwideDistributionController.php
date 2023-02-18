<?php

namespace App\Domain\Worldwide\Controllers\V1\Quote;

use App\Domain\Discount\Resources\V1\ApplicableDiscountCollection;
use App\Domain\DocumentMapping\Models\MappedRow;
use App\Domain\QuoteFile\Resources\V1\StoredQuoteFile;
use App\Domain\Worldwide\Contracts\ProcessesWorldwideDistributionState;
use App\Domain\Worldwide\Contracts\ProcessesWorldwideQuoteState;
use App\Domain\Worldwide\Models\DistributionRowsGroup;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Queries\WorldwideDistributionQueries;
use App\Domain\Worldwide\Requests\Quote\ApplyDiscountsRequest;
use App\Domain\Worldwide\Requests\Quote\CreateRowsGroupRequest;
use App\Domain\Worldwide\Requests\Quote\DeleteRowsGroupRequest;
use App\Domain\Worldwide\Requests\Quote\InitDistributionRequest;
use App\Domain\Worldwide\Requests\Quote\MoveRowsBetweenGroupsRequest;
use App\Domain\Worldwide\Requests\Quote\ProcessDistributionsRequest;
use App\Domain\Worldwide\Requests\Quote\RowsLookupRequest;
use App\Domain\Worldwide\Requests\Quote\SelectDistributionsRowsRequest;
use App\Domain\Worldwide\Requests\Quote\SetDistributionsMarginRequest;
use App\Domain\Worldwide\Requests\Quote\ShowDistributionApplicableDiscountsRequest;
use App\Domain\Worldwide\Requests\Quote\ShowMarginAfterCustomDiscountRequest;
use App\Domain\Worldwide\Requests\Quote\ShowMarginAfterPredefinedDiscountsRequest;
use App\Domain\Worldwide\Requests\Quote\ShowPriceDataAfterMarginTaxRequest;
use App\Domain\Worldwide\Requests\Quote\StoreDistributorFileRequest;
use App\Domain\Worldwide\Requests\Quote\StoreScheduleFileRequest;
use App\Domain\Worldwide\Requests\Quote\UpdateDetailsRequest;
use App\Domain\Worldwide\Requests\Quote\UpdateDistributionsMappingRequest;
use App\Domain\Worldwide\Requests\Quote\UpdateRowsGroupRequest;
use App\Domain\Worldwide\Requests\UpdateDistributionMappedRowRequest;
use App\Domain\Worldwide\Resources\V1\DistributorQuote\RowsGroup;
use App\Domain\Worldwide\Resources\V1\Quote\ContractAsset;
use App\Domain\Worldwide\Resources\V1\Quote\PriceSummary;
use App\Domain\Worldwide\Services\WorldwideQuote\Calculation\WorldwideDistributorQuoteCalc;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteDataMapper;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteVersionGuard;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class WorldwideDistributionController extends Controller
{
    protected ProcessesWorldwideDistributionState $processor;

    public function __construct(ProcessesWorldwideDistributionState $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Initialize a new Worldwide Distribution.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function initializeDistribution(InitDistributionRequest $request, WorldwideQuoteVersionGuard $versionGuard): JsonResponse
    {
        $this->authorize('update', $request->getQuote());

        $version = $versionGuard->resolveModelForActingUser($request->getQuote(), $request->user());

        $resource = $this->processor->initializeDistribution(
            $version
        );

        return response()->json(
            $resource,
            Response::HTTP_CREATED
        );
    }

    /**
     * Process Worldwide distributions import.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function processDistributions(ProcessDistributionsRequest $request,
                                         WorldwideQuoteVersionGuard $versionGuard,
                                         ProcessesWorldwideQuoteState $quoteProcessor): JsonResponse
    {
        $this->authorize('update', $request->getQuote());

        $version = $versionGuard->resolveModelForActingUser($request->getQuote(), $request->user());

        $quoteProcessor->processImportOfDistributorQuotes($version, $request->getDistributionCollection());

        return with($this->processor->validateDistributionsAfterImport($request->getDistributionCollection()), function (MessageBag $messageBag) use ($request) {
            if ($messageBag->isEmpty()) {
                return response()->json(true, Response::HTTP_OK);
            }

            throw new UnprocessableEntityHttpException($request->formatErrorsBag($messageBag));
        });
    }

    /**
     * Update Worldwide Distributions mapping.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function updateDistributionsMapping(UpdateDistributionsMappingRequest $request,
                                               WorldwideQuoteVersionGuard $versionGuard,
                                               ProcessesWorldwideQuoteState $quoteProcessor): Response
    {
        $this->authorize('update', $request->getQuote());

        $version = $versionGuard->resolveModelForActingUser($request->getQuote(), $request->user());

        $quoteProcessor->processQuoteMappingStep($version, $request->getStage());

        return response()->noContent();
    }

    /**
     * Update rows selection of Worldwide Distributions.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function updateRowsSelection(SelectDistributionsRowsRequest $request,
                                        WorldwideQuoteVersionGuard $versionGuard,
                                        ProcessesWorldwideQuoteState $quoteProcessor): Response
    {
        $this->authorize('update', $request->getQuote());

        $version = $versionGuard->resolveModelForActingUser($request->getQuote(), $request->user());

        $quoteProcessor->processQuoteMappingReviewStep($version, $request->getStage());

        return response()->noContent();
    }

    /**
     * Set Worldwide Distributions margin.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function setDistributionsMargin(SetDistributionsMarginRequest $request,
                                           WorldwideQuoteVersionGuard $versionGuard,
                                           ProcessesWorldwideQuoteState $quoteProcessor): Response
    {
        $this->authorize('update', $request->getQuote());

        $version = $versionGuard->resolveModelForActingUser($request->getQuote(), $request->user());

        $quoteProcessor->processQuoteMarginStep($version, $request->getStage());

        return response()->noContent();
    }

    /**
     * Store a newly created rows group of the worldwide distribution.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function createRowsGroup(CreateRowsGroupRequest $request,
                                    WorldwideQuoteVersionGuard $versionGuard,
                                    WorldwideDistribution $worldwideDistribution): JsonResponse
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideDistribution->worldwideQuote->worldwideQuote, $request->user());

        // TODO: process inside WorldwideQuoteStateProcessor.
        $resource = $this->processor->createRowsGroup($version, $worldwideDistribution, $request->getRowsGroupData());

        return response()->json(
            RowsGroup::make($request->loadGroupAttributes($resource)),
            Response::HTTP_CREATED
        );
    }

    /**
     * Update the specified rows group of the worldwide distribution.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function updateRowsGroup(UpdateRowsGroupRequest $request,
                                    WorldwideQuoteVersionGuard $versionGuard,
                                    WorldwideDistribution $worldwideDistribution,
                                    DistributionRowsGroup $rowsGroup): JsonResponse
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideDistribution->worldwideQuote->worldwideQuote, $request->user());

        // TODO: process inside WorldwideQuoteStateProcessor.
        $resource = $this->processor->updateRowsGroup(
            $version,
            $worldwideDistribution,
            $rowsGroup,
            $request->getRowsGroupData()
        );

        return response()->json(
            RowsGroup::make($request->loadGroupAttributes($resource)),
            Response::HTTP_OK
        );
    }

    /**
     * Delete the specified rows group from the worldwide distribution.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function deleteRowsGroup(DeleteRowsGroupRequest $request,
                                    WorldwideQuoteVersionGuard $versionGuard,
                                    WorldwideDistribution $worldwideDistribution,
                                    DistributionRowsGroup $rowsGroup): Response
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideDistribution->worldwideQuote->worldwideQuote, $request->user());

        // TODO: process inside WorldwideQuoteStateProcessor.
        $this->processor->deleteRowsGroup($version, $worldwideDistribution, $rowsGroup);

        return response()->noContent();
    }

    /**
     * Move rows between rows groups of the specified worldwide distribution.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function moveRowsBetweenGroups(MoveRowsBetweenGroupsRequest $request,
                                          WorldwideQuoteVersionGuard $versionGuard,
                                          WorldwideDistribution $worldwideDistribution): JsonResponse
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideDistribution->worldwideQuote->worldwideQuote, $request->user());

        // TODO: process inside WorldwideQuoteStateProcessor.
        $this->processor->moveRowsBetweenGroups(
            $version,
            $worldwideDistribution,
            $request->getOutputRowsGroup(),
            $request->getInputRowsGroup(),
            $request->getMovedRows()
        );

        return response()->json($request->getChangedRowsGroups(), Response::HTTP_OK);
    }

    /**
     * Perform rows lookup for rows group.
     *
     * @throws AuthorizationException
     */
    public function performRowsLookup(RowsLookupRequest $rowsLookup,
                                      WorldwideDistributionQueries $queries,
                                      WorldwideDistribution $worldwideDistribution,
                                      WorldwideQuoteDataMapper $dataMapper): JsonResponse
    {
        $this->authorize('view', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $resource = $queries->rowsLookupQuery(
            distribution: $worldwideDistribution,
            data: $rowsLookup->getRowsLookupData()
        )->get();

        $dataMapper->markExclusivityOfWorldwideDistributionRowsForCustomer(distributorQuote: $worldwideDistribution, rows: $resource);
        $dataMapper->includeContractDurationToContractAssets(distributorQuote: $worldwideDistribution, rows: $resource);

        return response()->json(
            data: $resource
        );
    }

    /**
     * Display applicable discounts for the specified worldwide distribution.
     *
     * @throws AuthorizationException
     */
    public function showDistributionApplicableDiscounts(ShowDistributionApplicableDiscountsRequest $request, WorldwideDistribution $worldwideDistribution): JsonResponse
    {
        $this->authorize('view', $worldwideDistribution->worldwideQuote->worldwideQuote);

        return response()->json(
            ApplicableDiscountCollection::make($request->getApplicableDiscounts())
        );
    }

    /**
     * Displays the distribution margin value after each sequentially applied predefined discount.
     *
     * @throws AuthorizationException
     */
    public function showMarginAfterPredefinedDiscounts(ShowMarginAfterPredefinedDiscountsRequest $request,
                                                       WorldwideDistribution $worldwideDistribution,
                                                       WorldwideDistributorQuoteCalc $calcService): JsonResponse
    {
        $this->authorize('view', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $resource = $calcService->calculatePriceSummaryOfDistributorQuoteAfterPredefinedDiscounts(
            $worldwideDistribution, $request->getApplicableDiscounts()
        );

        return response()->json(
            PriceSummary::make($resource)
        );
    }

    /**
     * Display the distribution margin value after applied custom discount.
     *
     * @throws AuthorizationException
     */
    public function showMarginAfterCustomDiscount(ShowMarginAfterCustomDiscountRequest $request,
                                                  WorldwideDistribution $worldwideDistribution,
                                                  WorldwideDistributorQuoteCalc $calcService): JsonResponse
    {
        $this->authorize('view', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $resource = $calcService->calculatePriceSummaryAfterCustomDiscount($worldwideDistribution, $request->getCustomDiscountData());

        return response()->json(
            PriceSummary::make($resource)
        );
    }

    /**
     * Display the price summary after applied country margin and tax.
     * (total_price, buy_price, final_total_price, margin_value_after_margin_tax).
     *
     * @throws AuthorizationException
     */
    public function showPriceSummaryAfterMarginTax(ShowPriceDataAfterMarginTaxRequest $request,
                                                   WorldwideDistribution $worldwideDistribution,
                                                   WorldwideDistributorQuoteCalc $calcService): JsonResponse
    {
        $this->authorize('view', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $resource = $calcService->calculatePriceSummaryAfterMarginTax($worldwideDistribution, $request->getMarginTaxData());

        return response()->json(
            PriceSummary::make($resource)
        );
    }

    /**
     * Apply specified discounts to the each worldwide distribution.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function applyDiscounts(ApplyDiscountsRequest $request,
                                   WorldwideQuoteVersionGuard $versionGuard,
                                   ProcessesWorldwideQuoteState $quoteProcessor): Response
    {
        $this->authorize('update', $request->getQuote());

        $version = $versionGuard->resolveModelForActingUser($request->getQuote(), $request->user());

        $quoteProcessor->processQuoteDiscountStep(
            $version,
            $request->getStage()
        );

        return response()->noContent();
    }

    /**
     * Update details of the worldwide distributions.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function updateDetails(UpdateDetailsRequest $request,
                                  WorldwideQuoteVersionGuard $versionGuard,
                                  ProcessesWorldwideQuoteState $quoteProcessor): Response
    {
        $this->authorize('update', $request->getQuote());

        $version = $versionGuard->resolveModelForActingUser($request->getQuote(), $request->user());

        $quoteProcessor->processContractQuoteDetailsStep(
            $version,
            $request->getStage()
        );

        return response()->noContent();
    }

    /**
     * Store a new Distributor file for the specified Worldwide Distribution.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function storeDistributorFile(StoreDistributorFileRequest $request,
                                         WorldwideQuoteVersionGuard $versionGuard,
                                         WorldwideDistribution $worldwideDistribution): JsonResponse
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideDistribution->worldwideQuote->worldwideQuote, $request->user());

        $resource = $this->processor->storeDistributorFile(
            $version,
            $worldwideDistribution,
            $request->file('file'),
        );

        return response()->json(
            StoredQuoteFile::make($resource),
            Response::HTTP_CREATED
        );
    }

    /**
     * Store a new Payment Schedule file for the specified Worldwide Distribution.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function storeScheduleFile(StoreScheduleFileRequest $request,
                                      WorldwideQuoteVersionGuard $versionGuard,
                                      WorldwideDistribution $worldwideDistribution): JsonResponse
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideDistribution->worldwideQuote->worldwideQuote, $request->user());

        $resource = $this->processor->storeScheduleFile(
            $version,
            $worldwideDistribution,
            $request->file('file'),
        );

        return response()->json(
            StoredQuoteFile::make($resource),
            Response::HTTP_CREATED
        );
    }

    /**
     * Show the specified mapped row entity.
     */
    public function showMappedRow(WorldwideDistribution $worldwideDistribution,
                                  MappedRow $mappedRow): JsonResponse
    {
        return response()->json(
            data: ContractAsset::make($mappedRow),
            status: Response::HTTP_OK
        );
    }

    /**
     * Update the existing mapped row of worldwide distribution.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function updateMappedRow(UpdateDistributionMappedRowRequest $request,
                                    WorldwideQuoteVersionGuard $versionGuard,
                                    WorldwideQuoteDataMapper $dataMapper,
                                    WorldwideDistribution $worldwideDistribution,
                                    MappedRow $mappedRow): JsonResponse
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideDistribution->worldwideQuote->worldwideQuote, $request->user());

        // TODO: process inside WorldwideQuoteStateProcessor.
        $resource = $this->processor->updateMappedRowOfDistribution(
            quote: $version,
            worldwideDistribution: $worldwideDistribution,
            mappedRow: $mappedRow,
            rowData: $request->getUpdateMappedRowFieldCollection(),
        );

        $dataMapper->markExclusivityOfWorldwideDistributionRowsForCustomer($worldwideDistribution, $resource);

        return response()->json(
            data: ContractAsset::make($resource),
            status: Response::HTTP_OK
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function destroy(Request $request,
                            WorldwideQuoteVersionGuard $versionGuard,
                            WorldwideDistribution $worldwideDistribution): Response
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideDistribution->worldwideQuote->worldwideQuote, $request->user());

        $this->processor->deleteDistribution($version, $worldwideDistribution);

        return response()->noContent();
    }
}
