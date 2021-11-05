<?php

namespace App\Http\Controllers\API\WorldwideQuotes;

use App\Contracts\Services\ProcessesWorldwideDistributionState;
use App\Contracts\Services\ProcessesWorldwideQuoteState;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use App\Http\Requests\{MappedRow\UpdateDistributionMappedRow,
    WorldwideQuote\ApplyDiscounts,
    WorldwideQuote\CreateRowsGroup,
    WorldwideQuote\DeleteRowsGroup,
    WorldwideQuote\InitDistribution,
    WorldwideQuote\MoveRowsBetweenGroups,
    WorldwideQuote\ProcessDistributions,
    WorldwideQuote\RowsLookup,
    WorldwideQuote\SelectDistributionsRows,
    WorldwideQuote\SetDistributionsMargin,
    WorldwideQuote\ShowDistributionApplicableDiscounts,
    WorldwideQuote\ShowMarginAfterCustomDiscount,
    WorldwideQuote\ShowMarginAfterPredefinedDiscounts,
    WorldwideQuote\ShowPriceDataAfterMarginTax,
    WorldwideQuote\StoreDistributorFile,
    WorldwideQuote\StoreScheduleFile,
    WorldwideQuote\UpdateDetails,
    WorldwideQuote\UpdateDistributionsMapping,
    WorldwideQuote\UpdateRowsGroup};
use App\Http\Resources\Discount\ApplicableDiscountCollection;
use App\Http\Resources\PriceSummary;
use App\Http\Resources\QuoteFile\StoredQuoteFile;
use App\Http\Resources\RowsGroup\RowsGroup;
use App\Http\Resources\WorldwideQuote\ContractAsset;
use App\Models\Quote\WorldwideDistribution;
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Models\QuoteFile\MappedRow;
use App\Queries\WorldwideDistributionQueries;
use App\Services\WorldwideQuote\Calculation\WorldwideDistributorQuoteCalc;
use App\Services\WorldwideQuote\WorldwideQuoteDataMapper;
use App\Services\WorldwideQuote\WorldwideQuoteVersionGuard;
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
     * @param InitDistribution $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function initializeDistribution(InitDistribution $request, WorldwideQuoteVersionGuard $versionGuard): JsonResponse
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
     * @param ProcessDistributions $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param \App\Contracts\Services\ProcessesWorldwideQuoteState $quoteProcessor
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function processDistributions(ProcessDistributions         $request,
                                         WorldwideQuoteVersionGuard   $versionGuard,
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
     * @param UpdateDistributionsMapping $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param ProcessesWorldwideQuoteState $quoteProcessor
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function updateDistributionsMapping(UpdateDistributionsMapping   $request,
                                               WorldwideQuoteVersionGuard   $versionGuard,
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
     * @param SelectDistributionsRows $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param ProcessesWorldwideQuoteState $quoteProcessor
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function updateRowsSelection(SelectDistributionsRows      $request,
                                        WorldwideQuoteVersionGuard   $versionGuard,
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
     * @param SetDistributionsMargin $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param ProcessesWorldwideQuoteState $quoteProcessor
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function setDistributionsMargin(SetDistributionsMargin       $request,
                                           WorldwideQuoteVersionGuard   $versionGuard,
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
     * @param CreateRowsGroup $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideDistribution $worldwideDistribution
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function createRowsGroup(CreateRowsGroup            $request,
                                    WorldwideQuoteVersionGuard $versionGuard,
                                    WorldwideDistribution      $worldwideDistribution): JsonResponse
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
     * @param UpdateRowsGroup $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideDistribution $worldwideDistribution
     * @param DistributionRowsGroup $rowsGroup
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function updateRowsGroup(UpdateRowsGroup            $request,
                                    WorldwideQuoteVersionGuard $versionGuard,
                                    WorldwideDistribution      $worldwideDistribution,
                                    DistributionRowsGroup      $rowsGroup): JsonResponse
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
     * @param DeleteRowsGroup $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideDistribution $worldwideDistribution
     * @param DistributionRowsGroup $rowsGroup
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function deleteRowsGroup(DeleteRowsGroup            $request,
                                    WorldwideQuoteVersionGuard $versionGuard,
                                    WorldwideDistribution      $worldwideDistribution,
                                    DistributionRowsGroup      $rowsGroup): Response
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
     * @param MoveRowsBetweenGroups $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideDistribution $worldwideDistribution
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function moveRowsBetweenGroups(MoveRowsBetweenGroups      $request,
                                          WorldwideQuoteVersionGuard $versionGuard,
                                          WorldwideDistribution      $worldwideDistribution): JsonResponse
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
     * @param RowsLookup $rowsLookup
     * @param WorldwideDistributionQueries $queries
     * @param WorldwideDistribution $worldwideDistribution
     * @param WorldwideQuoteDataMapper $dataMapper
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function performRowsLookup(RowsLookup                   $rowsLookup,
                                      WorldwideDistributionQueries $queries,
                                      WorldwideDistribution        $worldwideDistribution,
                                      WorldwideQuoteDataMapper     $dataMapper): JsonResponse
    {
        $this->authorize('view', $worldwideDistribution->worldwideQuote->worldwideQuote);


        $resource = $queries->rowsLookupQuery(
            distribution: $worldwideDistribution,
            data: $rowsLookup->getRowsLookupData()
        )->get();

        $dataMapper->markExclusivityOfWorldwideDistributionRowsForCustomer(distributorQuote: $worldwideDistribution, rows: $resource);


        return response()->json(
            data: $resource
        );
    }

    /**
     * Display applicable discounts for the specified worldwide distribution.
     *
     * @param ShowDistributionApplicableDiscounts $request
     * @param WorldwideDistribution $worldwideDistribution
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showDistributionApplicableDiscounts(ShowDistributionApplicableDiscounts $request, WorldwideDistribution $worldwideDistribution): JsonResponse
    {
        $this->authorize('view', $worldwideDistribution->worldwideQuote->worldwideQuote);

        return response()->json(
            ApplicableDiscountCollection::make($request->getApplicableDiscounts())
        );
    }

    /**
     * Displays the distribution margin value after each sequentially applied predefined discount.
     *
     * @param ShowMarginAfterPredefinedDiscounts $request
     * @param WorldwideDistribution $worldwideDistribution
     * @param WorldwideDistributorQuoteCalc $calcService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showMarginAfterPredefinedDiscounts(ShowMarginAfterPredefinedDiscounts $request,
                                                       WorldwideDistribution              $worldwideDistribution,
                                                       WorldwideDistributorQuoteCalc      $calcService): JsonResponse
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
     * @param ShowMarginAfterCustomDiscount $request
     * @param WorldwideDistribution $worldwideDistribution
     * @param \App\Services\WorldwideQuote\Calculation\WorldwideDistributorQuoteCalc $calcService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showMarginAfterCustomDiscount(ShowMarginAfterCustomDiscount $request,
                                                  WorldwideDistribution         $worldwideDistribution,
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
     * (total_price, buy_price, final_total_price, margin_value_after_margin_tax)
     *
     * @param ShowPriceDataAfterMarginTax $request
     * @param WorldwideDistribution $worldwideDistribution
     * @param \App\Services\WorldwideQuote\Calculation\WorldwideDistributorQuoteCalc $calcService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showPriceSummaryAfterMarginTax(ShowPriceDataAfterMarginTax   $request,
                                                   WorldwideDistribution         $worldwideDistribution,
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
     * @param ApplyDiscounts $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param ProcessesWorldwideQuoteState $quoteProcessor
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function applyDiscounts(ApplyDiscounts               $request,
                                   WorldwideQuoteVersionGuard   $versionGuard,
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
     * @param UpdateDetails $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param ProcessesWorldwideQuoteState $quoteProcessor
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function updateDetails(UpdateDetails                $request,
                                  WorldwideQuoteVersionGuard   $versionGuard,
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
     * @param StoreDistributorFile $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideDistribution $worldwideDistribution
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function storeDistributorFile(StoreDistributorFile       $request,
                                         WorldwideQuoteVersionGuard $versionGuard,
                                         WorldwideDistribution      $worldwideDistribution): JsonResponse
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
     * @param StoreScheduleFile $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideDistribution $worldwideDistribution
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function storeScheduleFile(StoreScheduleFile          $request,
                                      WorldwideQuoteVersionGuard $versionGuard,
                                      WorldwideDistribution      $worldwideDistribution): JsonResponse
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
     * Update the existing mapped row of worldwide distribution.
     *
     * @param UpdateDistributionMappedRow $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideDistribution $worldwideDistribution
     * @param MappedRow $mappedRow
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function updateMappedRow(UpdateDistributionMappedRow $request,
                                    WorldwideQuoteVersionGuard  $versionGuard,
                                    WorldwideQuoteDataMapper    $dataMapper,
                                    WorldwideDistribution       $worldwideDistribution,
                                    MappedRow                   $mappedRow): JsonResponse
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
     * @param Request $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideDistribution $worldwideDistribution
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function destroy(Request                    $request,
                            WorldwideQuoteVersionGuard $versionGuard,
                            WorldwideDistribution      $worldwideDistribution): Response
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideDistribution->worldwideQuote->worldwideQuote, $request->user());

        $this->processor->deleteDistribution($version, $worldwideDistribution);

        return response()->noContent();
    }
}
