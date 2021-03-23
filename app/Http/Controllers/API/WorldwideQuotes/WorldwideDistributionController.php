<?php

namespace App\Http\Controllers\API\WorldwideQuotes;

use App\Contracts\Services\ProcessesWorldwideDistributionState;
use App\Contracts\Services\ProcessesWorldwideQuoteState;
use App\Http\Controllers\Controller;
use App\Http\Requests\MappedRow\UpdateDistributionMappedRow;
use App\Http\Requests\WorldwideQuote\ApplyDiscounts;
use App\Http\Requests\WorldwideQuote\CreateRowsGroup;
use App\Http\Requests\WorldwideQuote\DeleteRowsGroup;
use App\Http\Requests\WorldwideQuote\InitDistribution;
use App\Http\Requests\WorldwideQuote\MoveRowsBetweenGroups;
use App\Http\Requests\WorldwideQuote\ProcessDistributions;
use App\Http\Requests\WorldwideQuote\RowsLookup;
use App\Http\Requests\WorldwideQuote\SelectDistributionsRows;
use App\Http\Requests\WorldwideQuote\SetDistributionsMargin;
use App\Http\Requests\WorldwideQuote\ShowDistributionApplicableDiscounts;
use App\Http\Requests\WorldwideQuote\ShowMarginAfterCustomDiscount;
use App\Http\Requests\WorldwideQuote\ShowMarginAfterPredefinedDiscounts;
use App\Http\Requests\WorldwideQuote\ShowPriceDataAfterMarginTax;
use App\Http\Requests\WorldwideQuote\StoreDistributorFile;
use App\Http\Requests\WorldwideQuote\StoreScheduleFile;
use App\Http\Requests\WorldwideQuote\UpdateDetails;
use App\Http\Requests\WorldwideQuote\UpdateDistributionsMapping;
use App\Http\Requests\WorldwideQuote\UpdateRowsGroup;
use App\Http\Resources\Discount\ApplicableDiscountCollection;
use App\Http\Resources\PriceSummary;
use App\Http\Resources\QuoteFile\StoredQuoteFile;
use App\Http\Resources\RowsGroup\RowsGroup;
use App\Models\Quote\WorldwideDistribution;
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Models\QuoteFile\MappedRow;
use App\Queries\WorldwideDistributionQueries;
use App\Services\WorldwideDistributionCalc;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
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
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function initializeDistribution(InitDistribution $request): JsonResponse
    {
        $this->authorize('update', $request->getWorldwideQuote());

        $resource = $this->processor->initializeDistribution(
            $request->getWorldwideQuote()
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
     * @return JsonResponse
     */
    public function processDistributions(ProcessDistributions $request): JsonResponse
    {
        $this->processor->processDistributionsImport($request->getDistributionCollection());

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
     * @param ProcessesWorldwideQuoteState $quoteProcessor
     * @return Response
     */
    public function updateDistributionsMapping(UpdateDistributionsMapping $request, ProcessesWorldwideQuoteState $quoteProcessor): Response
    {
        $this->processor->processDistributionsMapping($request->getMappingCollection());

        $quoteProcessor->processQuoteMappingStep($request->getStage());

        return response()->noContent();
    }

    /**
     * Update rows selection of Worldwide Distributions.
     *
     * @param SelectDistributionsRows $request
     * @param ProcessesWorldwideQuoteState $quoteProcessor
     * @return Response
     */
    public function updateRowsSelection(SelectDistributionsRows $request, ProcessesWorldwideQuoteState $quoteProcessor): Response
    {
        $this->processor->updateRowsSelection($request->getSelectedDistributionRowsCollection());

        $quoteProcessor->processQuoteMappingReviewStep($request->getStage());

        return response()->noContent();
    }

    /**
     * Set Worldwide Distributions margin.
     *
     * @param SetDistributionsMargin $request
     * @param ProcessesWorldwideQuoteState $quoteProcessor
     * @return Response
     */
    public function setDistributionsMargin(SetDistributionsMargin $request, ProcessesWorldwideQuoteState $quoteProcessor): Response
    {
        $this->processor->setDistributionsMargin($request->getDistributionMarginCollection());

        $quoteProcessor->processQuoteMarginStep($request->getStage());

        return response()->noContent();
    }

    /**
     * Store a newly created rows group of the worldwide distribution.
     *
     * @param CreateRowsGroup $request
     * @param WorldwideDistribution $worldwideDistribution
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function createRowsGroup(CreateRowsGroup $request, WorldwideDistribution $worldwideDistribution): JsonResponse
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote);

        $resource = $this->processor->createRowsGroup($worldwideDistribution, $request->getRowsGroupData());

        return response()->json(
            RowsGroup::make($request->loadGroupAttributes($resource)),
            Response::HTTP_CREATED
        );
    }

    /**
     * Update the specified rows group of the worldwide distribution.
     *
     * @param UpdateRowsGroup $request
     * @param WorldwideDistribution $worldwideDistribution
     * @param DistributionRowsGroup $rowsGroup
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateRowsGroup(UpdateRowsGroup $request, WorldwideDistribution $worldwideDistribution, DistributionRowsGroup $rowsGroup): JsonResponse
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote);

        $resource = $this->processor->updateRowsGroup(
            $worldwideDistribution, $rowsGroup, $request->getRowsGroupData()
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
     * @param WorldwideDistribution $worldwideDistribution
     * @param DistributionRowsGroup $rowsGroup
     * @return Response
     * @throws AuthorizationException
     */
    public function deleteRowsGroup(DeleteRowsGroup $request, WorldwideDistribution $worldwideDistribution, DistributionRowsGroup $rowsGroup): Response
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote);

        $this->processor->deleteRowsGroup($worldwideDistribution, $rowsGroup);

        return response()->noContent();
    }

    /**
     * Move rows between rows groups of the specified worldwide distribution.
     *
     * @param MoveRowsBetweenGroups $request
     * @param WorldwideDistribution $worldwideDistribution
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function moveRowsBetweenGroups(MoveRowsBetweenGroups $request, WorldwideDistribution $worldwideDistribution): JsonResponse
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote);

        $this->processor->moveRowsBetweenGroups(
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
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function performRowsLookup(RowsLookup $rowsLookup, WorldwideDistributionQueries $queries, WorldwideDistribution $worldwideDistribution): JsonResponse
    {
        $this->authorize('view', $worldwideDistribution->worldwideQuote);

        return response()->json(
            $queries->rowsLookupQuery(
                $worldwideDistribution,
                $rowsLookup->getRowsLookupData()
            )->get()
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
        $this->authorize('view', $worldwideDistribution->worldwideQuote);

        return response()->json(
            ApplicableDiscountCollection::make($request->getApplicableDiscounts())
        );
    }

    /**
     * Displays the distribution margin value after each sequentially applied predefined discount.
     *
     * @param ShowMarginAfterPredefinedDiscounts $request
     * @param WorldwideDistribution $worldwideDistribution
     * @param WorldwideDistributionCalc $calcService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showMarginAfterPredefinedDiscounts(ShowMarginAfterPredefinedDiscounts $request,
                                                       WorldwideDistribution $worldwideDistribution,
                                                       WorldwideDistributionCalc $calcService): JsonResponse
    {
        $this->authorize('view', $worldwideDistribution->worldwideQuote);

        $resource = $calcService->calculatePriceSummaryAfterPredefinedDiscounts(
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
     * @param WorldwideDistributionCalc $calcService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showMarginAfterCustomDiscount(ShowMarginAfterCustomDiscount $request,
                                                  WorldwideDistribution $worldwideDistribution,
                                                  WorldwideDistributionCalc $calcService): JsonResponse
    {
        $this->authorize('view', $worldwideDistribution->worldwideQuote);

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
     * @param WorldwideDistributionCalc $calcService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showPriceSummaryAfterMarginTax(ShowPriceDataAfterMarginTax $request,
                                                   WorldwideDistribution $worldwideDistribution,
                                                   WorldwideDistributionCalc $calcService): JsonResponse
    {
        $this->authorize('view', $worldwideDistribution->worldwideQuote);

        $resource = $calcService->calculatePriceSummaryAfterMarginTax($worldwideDistribution, $request->getMarginTaxData());

        return response()->json(
            PriceSummary::make($resource)
        );
    }

    /**
     * Apply specified discounts to the each worldwide distribution.
     *
     * @param ApplyDiscounts $request
     * @param ProcessesWorldwideQuoteState $quoteProcessor
     * @return Response
     * @throws AuthorizationException
     */
    public function applyDiscounts(ApplyDiscounts $request, ProcessesWorldwideQuoteState $quoteProcessor): Response
    {
        $this->authorize('update', $request->getQuote());

        $this->processor->applyDistributionsDiscount(
            $request->getDistributionDiscountsCollection()
        );

        $quoteProcessor->processQuoteDiscountStep(
            $request->getQuote(),
            $request->getStage()
        );

        return response()->noContent();
    }

    /**
     * Update details of the worldwide distributions.
     *
     * @param UpdateDetails $request
     * @param ProcessesWorldwideQuoteState $quoteProcessor
     * @return Response
     * @throws AuthorizationException
     */
    public function updateDetails(UpdateDetails $request, ProcessesWorldwideQuoteState $quoteProcessor): Response
    {
        $this->authorize('update', $request->getQuote());

        $this->processor->updateDistributionsDetails(
            $request->getDistributionDetailsCollection()
        );

        $quoteProcessor->processContractQuoteDetailsStep(
            $request->getQuote(),
            $request->getStage()
        );

        return response()->noContent();
    }

    /**
     * Store a new Distributor file for the specified Worldwide Distribution.
     *
     * @param StoreDistributorFile $request
     * @param WorldwideDistribution $worldwideDistribution
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function storeDistributorFile(StoreDistributorFile $request, WorldwideDistribution $worldwideDistribution): JsonResponse
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote);

        $resource = $this->processor->storeDistributorFile(
            $request->file('file'),
            $worldwideDistribution,
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
     * @param WorldwideDistribution $worldwideDistribution
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function storeScheduleFile(StoreScheduleFile $request, WorldwideDistribution $worldwideDistribution): JsonResponse
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote);

        $resource = $this->processor->storeScheduleFile(
            $request->file('file'),
            $worldwideDistribution,
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
     * @param WorldwideDistribution $worldwideDistribution
     * @param MappedRow $mappedRow
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateMappedRow(UpdateDistributionMappedRow $request,
                                    WorldwideDistribution $worldwideDistribution,
                                    MappedRow $mappedRow): JsonResponse
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote);

        $resource = $this->processor->updateMappedRowOfDistribution(
            $request->getUpdateMappedRowFieldCollection(),
            $mappedRow,
            $worldwideDistribution
        );

        return response()->json(
            $resource,
            Response::HTTP_OK
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param WorldwideDistribution $worldwideDistribution
     * @return Response
     * @throws AuthorizationException
     */
    public function destroy(WorldwideDistribution $worldwideDistribution): Response
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote);

        $this->processor->deleteDistribution($worldwideDistribution);

        return response()->noContent();
    }
}
