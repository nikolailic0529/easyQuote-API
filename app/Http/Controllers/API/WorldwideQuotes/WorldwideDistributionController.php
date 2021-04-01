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
     * @return JsonResponse
     * @throws AuthorizationException|\Throwable
     */
    public function initializeDistribution(InitDistribution $request): JsonResponse
    {
        $this->authorize('update', $request->getQuote());

        $version = (new WorldwideQuoteVersionGuard($request->getQuote(), $request->user()))->resolveModelForActingUser();

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
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws \Throwable
     */
    public function processDistributions(ProcessDistributions $request): JsonResponse
    {
        $this->authorize('update', $request->getQuote());

        $version = (new WorldwideQuoteVersionGuard($request->getQuote(), $request->user()))->resolveModelForActingUser();

        $this->processor->processDistributionsImport($version, $request->getDistributionCollection());

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
     * @throws AuthorizationException|\Throwable
     */
    public function updateDistributionsMapping(UpdateDistributionsMapping $request, ProcessesWorldwideQuoteState $quoteProcessor): Response
    {
        $this->authorize('update', $request->getQuote());

        $version = (new WorldwideQuoteVersionGuard($request->getQuote(), $request->user()))->resolveModelForActingUser();

        $this->processor->processDistributionsMapping($version, $request->getMappingCollection());

        $quoteProcessor->processQuoteMappingStep($version, $request->getStage());

        return response()->noContent();
    }

    /**
     * Update rows selection of Worldwide Distributions.
     *
     * @param SelectDistributionsRows $request
     * @param ProcessesWorldwideQuoteState $quoteProcessor
     * @return Response
     * @throws AuthorizationException|\Throwable
     */
    public function updateRowsSelection(SelectDistributionsRows $request, ProcessesWorldwideQuoteState $quoteProcessor): Response
    {
        $this->authorize('update', $request->getQuote());

        $version = (new WorldwideQuoteVersionGuard($request->getQuote(), $request->user()))->resolveModelForActingUser();

        $this->processor->updateRowsSelection($version, $request->getSelectedDistributionRowsCollection());

        $quoteProcessor->processQuoteMappingReviewStep($version, $request->getStage());

        return response()->noContent();
    }

    /**
     * Set Worldwide Distributions margin.
     *
     * @param SetDistributionsMargin $request
     * @param ProcessesWorldwideQuoteState $quoteProcessor
     * @return Response
     * @throws \Throwable
     */
    public function setDistributionsMargin(SetDistributionsMargin $request, ProcessesWorldwideQuoteState $quoteProcessor): Response
    {
        $this->authorize('update', $request->getQuote());

        $version = (new WorldwideQuoteVersionGuard($request->getQuote(), $request->user()))->resolveModelForActingUser();

        $this->processor->setDistributionsMargin($version, $request->getDistributionMarginCollection());

        $quoteProcessor->processQuoteMarginStep($version, $request->getStage());

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
        $this->authorize('update', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $version = (new WorldwideQuoteVersionGuard($worldwideDistribution->worldwideQuote->worldwideQuote, $request->user()))->resolveModelForActingUser();

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
     * @param WorldwideDistribution $worldwideDistribution
     * @param DistributionRowsGroup $rowsGroup
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateRowsGroup(UpdateRowsGroup $request, WorldwideDistribution $worldwideDistribution, DistributionRowsGroup $rowsGroup): JsonResponse
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $version = (new WorldwideQuoteVersionGuard($worldwideDistribution->worldwideQuote->worldwideQuote, $request->user()))->resolveModelForActingUser();

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
     * @param WorldwideDistribution $worldwideDistribution
     * @param DistributionRowsGroup $rowsGroup
     * @return Response
     * @throws AuthorizationException
     */
    public function deleteRowsGroup(DeleteRowsGroup $request, WorldwideDistribution $worldwideDistribution, DistributionRowsGroup $rowsGroup): Response
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $version = (new WorldwideQuoteVersionGuard($worldwideDistribution->worldwideQuote->worldwideQuote, $request->user()))->resolveModelForActingUser();

        $this->processor->deleteRowsGroup($version, $worldwideDistribution, $rowsGroup);

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
        $this->authorize('update', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $version = (new WorldwideQuoteVersionGuard($worldwideDistribution->worldwideQuote->worldwideQuote, $request->user()))->resolveModelForActingUser();

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
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function performRowsLookup(RowsLookup $rowsLookup, WorldwideDistributionQueries $queries, WorldwideDistribution $worldwideDistribution): JsonResponse
    {
        $this->authorize('view', $worldwideDistribution->worldwideQuote->worldwideQuote);

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
     * @param WorldwideDistributionCalc $calcService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showMarginAfterPredefinedDiscounts(ShowMarginAfterPredefinedDiscounts $request,
                                                       WorldwideDistribution $worldwideDistribution,
                                                       WorldwideDistributionCalc $calcService): JsonResponse
    {
        $this->authorize('view', $worldwideDistribution->worldwideQuote->worldwideQuote);

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
     * @param WorldwideDistributionCalc $calcService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showPriceSummaryAfterMarginTax(ShowPriceDataAfterMarginTax $request,
                                                   WorldwideDistribution $worldwideDistribution,
                                                   WorldwideDistributionCalc $calcService): JsonResponse
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
     * @param ProcessesWorldwideQuoteState $quoteProcessor
     * @return Response
     * @throws AuthorizationException
     */
    public function applyDiscounts(ApplyDiscounts $request, ProcessesWorldwideQuoteState $quoteProcessor): Response
    {
        $this->authorize('update', $request->getQuote());

        $version = (new WorldwideQuoteVersionGuard($request->getQuote(), $request->user()))->resolveModelForActingUser();

        $this->processor->applyDistributionsDiscount(
            $version,
            $request->getDistributionDiscountsCollection()
        );

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
     * @param ProcessesWorldwideQuoteState $quoteProcessor
     * @return Response
     * @throws AuthorizationException|\Throwable
     */
    public function updateDetails(UpdateDetails $request, ProcessesWorldwideQuoteState $quoteProcessor): Response
    {
        $this->authorize('update', $request->getQuote());

        $version = (new WorldwideQuoteVersionGuard($request->getQuote(), $request->user()))->resolveModelForActingUser();

        $this->processor->updateDistributionsDetails(
            $version,
            $request->getDistributionDetailsCollection()
        );

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
     * @param WorldwideDistribution $worldwideDistribution
     * @return JsonResponse
     * @throws AuthorizationException|\Throwable
     */
    public function storeDistributorFile(StoreDistributorFile $request, WorldwideDistribution $worldwideDistribution): JsonResponse
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $version = (new WorldwideQuoteVersionGuard($worldwideDistribution->worldwideQuote->worldwideQuote, $request->user()))->resolveModelForActingUser();

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
     * @param WorldwideDistribution $worldwideDistribution
     * @return JsonResponse
     * @throws AuthorizationException|\Throwable
     */
    public function storeScheduleFile(StoreScheduleFile $request, WorldwideDistribution $worldwideDistribution): JsonResponse
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $version = (new WorldwideQuoteVersionGuard($worldwideDistribution->worldwideQuote->worldwideQuote, $request->user()))->resolveModelForActingUser();

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
     * @param WorldwideDistribution $worldwideDistribution
     * @param MappedRow $mappedRow
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateMappedRow(UpdateDistributionMappedRow $request,
                                    WorldwideDistribution $worldwideDistribution,
                                    MappedRow $mappedRow): JsonResponse
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $version = (new WorldwideQuoteVersionGuard($worldwideDistribution->worldwideQuote->worldwideQuote, $request->user()))->resolveModelForActingUser();

        $resource = $this->processor->updateMappedRowOfDistribution(
            $version,
            $worldwideDistribution,
            $mappedRow,
            $request->getUpdateMappedRowFieldCollection(),
        );

        return response()->json(
            $resource,
            Response::HTTP_OK
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param WorldwideDistribution $worldwideDistribution
     * @return Response
     * @throws AuthorizationException
     * @throws \Throwable
     */
    public function destroy(Request $request, WorldwideDistribution $worldwideDistribution): Response
    {
        $this->authorize('update', $worldwideDistribution->worldwideQuote->worldwideQuote);

        $version = (new WorldwideQuoteVersionGuard($worldwideDistribution->worldwideQuote->worldwideQuote, $request->user()))->resolveModelForActingUser();

        $this->processor->deleteDistribution($version, $worldwideDistribution);

        return response()->noContent();
    }
}
