<?php

namespace App\Http\Controllers\API\WorldwideQuotes;

use App\Contracts\Services\ManagesExchangeRates as ExchangeRateService;
use App\Contracts\Services\ProcessesWorldwideQuoteState;
use App\DTO\WorldwideQuote\WorldwideQuoteValidationResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\{WorldwideQuote\DraftQuote,
    WorldwideQuote\InitQuote,
    WorldwideQuote\MarkWorldwideQuoteAsDead,
    WorldwideQuote\ProcessQuoteAddressesContacts,
    WorldwideQuote\ProcessQuoteAssetsReviewStep,
    WorldwideQuote\ProcessQuoteDetails,
    WorldwideQuote\ProcessQuoteDiscountStep,
    WorldwideQuote\ProcessQuoteMarginStep,
    WorldwideQuote\ShowPackQuoteApplicableDiscounts,
    WorldwideQuote\ShowPriceSummaryOfContractQuoteAfterCountryMarginTax,
    WorldwideQuote\ShowPriceSummaryOfContractQuoteAfterDiscounts,
    WorldwideQuote\ShowPriceSummaryOfPackQuoteAfterCountryMarginTax,
    WorldwideQuote\ShowPriceSummaryOfPackQuoteAfterDiscounts,
    WorldwideQuote\ShowQuoteState,
    WorldwideQuote\SubmitQuote,
    WorldwideQuote\UpdateQuoteImport};
use App\Http\Resources\{Discount\ApplicableDiscountCollection,
    WorldwideQuote\ContractQuotePriceSummary,
    WorldwideQuote\PackQuotePriceSummary,
    WorldwideQuote\WorldwideQuoteState};
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Services\{WorldwideQuote\CollectWorldwideQuoteFilesService,
    WorldwideQuote\WorldwideQuoteCalc,
    WorldwideQuote\WorldwideQuoteDataMapper,
    WorldwideQuote\WorldwideQuoteExporter,
    WorldwideQuote\WorldwideQuoteValidator,
    WorldwideQuote\WorldwideQuoteVersionGuard};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorldwideQuoteController extends Controller
{
    protected ProcessesWorldwideQuoteState $processor;

    public function __construct(ProcessesWorldwideQuoteState $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Initialize a new Contract Worldwide Quote.
     *
     * @param InitQuote $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function initializeQuote(InitQuote $request): JsonResponse
    {
        $this->authorize('create', WorldwideQuote::class);

        $worldwideQuote = $this->processor->initializeQuote(
            $request->getStage()
        );

        $resource = WorldwideQuoteState::make($worldwideQuote);

        return response()->json(
            $resource,
            Response::HTTP_CREATED
        );
    }

    /**
     * Switch active version of Worldwide Quote.
     *
     * @param WorldwideQuote $worldwideQuote
     * @param WorldwideQuoteVersion $version
     * @return Response
     * @throws AuthorizationException
     */
    public function switchActiveVersionOfQuote(WorldwideQuote $worldwideQuote, WorldwideQuoteVersion $version): Response
    {
        $this->authorize('update', $worldwideQuote);

        $this->processor->switchActiveVersionOfQuote($worldwideQuote, $version);

        return response()->noContent();
    }

    /**
     * Delete inactive version of Worldwide Quote.
     *
     * @param WorldwideQuote $worldwideQuote
     * @param WorldwideQuoteVersion $version
     * @return Response
     * @throws AuthorizationException
     */
    public function destroyQuoteVersion(WorldwideQuote $worldwideQuote, WorldwideQuoteVersion $version): Response
    {
        $this->authorize('deleteVersion', [$worldwideQuote, $version]);

        $this->processor->deleteVersionOfQuote($worldwideQuote, $version);

        return response()->noContent();
    }

    /**
     * Process Worldwide Quote addresses & contacts step.
     *
     * @param ProcessQuoteAddressesContacts $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideQuote $worldwideQuote
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function processQuoteAddressesContactsStep(ProcessQuoteAddressesContacts $request,
                                                      WorldwideQuoteVersionGuard $versionGuard,
                                                      WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $resource = $this->processor->processQuoteAddressesContactsStep(
            $version,
            $request->getStage()
        );

        return response()->json(
            $resource,
            Response::HTTP_OK
        );
    }

    /**
     * Process Worldwide Quote import step.
     *
     * @param UpdateQuoteImport $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideQuote $worldwideQuote
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function processQuoteImportStep(
        UpdateQuoteImport $request,
        WorldwideQuoteVersionGuard $versionGuard,
        WorldwideQuote $worldwideQuote
    ): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $resource = $this->processor->processQuoteImportStep(
            $version,
            $request->getStage()
        );

        return response()->json(
            $worldwideQuote,
            Response::HTTP_OK
        );
    }

    /**
     * Process Worldwide Pack Quote assets review step.
     *
     * @param ProcessQuoteAssetsReviewStep $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideQuote $worldwideQuote
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function processQuoteAssetsReviewStep(ProcessQuoteAssetsReviewStep $request,
                                                 WorldwideQuoteVersionGuard $versionGuard,
                                                 WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $resource = $this->processor->processQuoteAssetsReviewStep(
            $version,
            $request->getStage()
        );

        return response()->json(
            $worldwideQuote,
            Response::HTTP_OK
        );
    }

    /**
     * Process Worldwide Quote margin step.
     *
     * @param ProcessQuoteMarginStep $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideQuote $worldwideQuote
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function processQuoteMarginStep(ProcessQuoteMarginStep $request,
                                           WorldwideQuoteVersionGuard $versionGuard,
                                           WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $resource = $this->processor->processPackQuoteMarginStep(
            $version,
            $request->getStage()
        );

        return response()->json(
            $resource,
            Response::HTTP_OK
        );
    }

    /**
     * Process Worldwide Quote discounts step.
     *
     * @param ProcessQuoteDiscountStep $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideQuote $worldwideQuote
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function processQuoteDiscountStep(ProcessQuoteDiscountStep $request,
                                             WorldwideQuoteVersionGuard $versionGuard,
                                             WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $resource = $this->processor->processPackQuoteDiscountStep(
            $version,
            $request->getStage()
        );

        return response()->json(
            $worldwideQuote,
            Response::HTTP_OK
        );
    }

    /**
     * Process Worldwide Pack Quote details step.
     *
     * @param ProcessQuoteDetails $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideQuote $worldwideQuote
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function processQuoteDetailsStep(ProcessQuoteDetails $request,
                                            WorldwideQuoteVersionGuard $versionGuard,
                                            WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $resource = $this->processor->processPackQuoteDetailsStep(
            $version,
            $request->getStage()
        );

        return response()->json(
            $worldwideQuote,
            Response::HTTP_OK
        );
    }

    /**
     * Display the specified quote state.
     *
     * @param ShowQuoteState $request
     * @param WorldwideQuote $worldwideQuote
     * @param ExchangeRateService $exchangeRateService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showQuoteState(ShowQuoteState $request,
                                   WorldwideQuote $worldwideQuote,
                                   ExchangeRateService $exchangeRateService): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);

        $resource = WorldwideQuoteState::make($worldwideQuote)
            ->setActualExchangeRate($exchangeRateService->getTargetRate(
                $worldwideQuote->activeVersion->quoteCurrency,
                $worldwideQuote->activeVersion->outputCurrency
            ));

        return response()->json(
            tap($resource, function (WorldwideQuoteState $resource) use ($request, $worldwideQuote) {
                filter($resource);

                $request->includeModelAttributes($worldwideQuote);
            }),
            Response::HTTP_OK
        );
    }

    /**
     * Download existing Distributor Files from Worldwide Quote.
     *
     * @param WorldwideQuote $worldwideQuote
     * @param CollectWorldwideQuoteFilesService $service
     * @return BinaryFileResponse
     * @throws AuthorizationException
     * @throws \Exception
     */
    public function downloadQuoteDistributorFiles(WorldwideQuote $worldwideQuote, CollectWorldwideQuoteFilesService $service): BinaryFileResponse
    {
        $this->authorize('view', $worldwideQuote);

        $file = $service->collectDistributorFilesFromQuote($worldwideQuote);

        return response()->download(
            $file
        );
    }

    /**
     * Download existing Payment Schedule Files from Worldwide Quote.
     *
     * @param WorldwideQuote $worldwideQuote
     * @param CollectWorldwideQuoteFilesService $service
     * @return BinaryFileResponse
     * @throws AuthorizationException
     * @throws \Exception
     */
    public function downloadQuoteScheduleFiles(WorldwideQuote $worldwideQuote, CollectWorldwideQuoteFilesService $service): BinaryFileResponse
    {
        $this->authorize('view', $worldwideQuote);

        $file = $service->collectScheduleFilesFromQuote($worldwideQuote);

        return response()->download(
            $file
        );
    }

    /**
     * Show data of Worldwide Quote to create a new Sales Order.
     *
     * @param WorldwideQuote $worldwideQuote
     * @param WorldwideQuoteDataMapper $dataMapper
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showSalesOrderDataOfWorldwideQuote(WorldwideQuote $worldwideQuote, WorldwideQuoteDataMapper $dataMapper): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);

        return response()->json($dataMapper->mapWorldwideQuoteSalesOrderData($worldwideQuote), Response::HTTP_OK);
    }

    /**
     * Show preview data of the specified Worldwide Quote.
     *
     * @param WorldwideQuote $worldwideQuote
     * @param WorldwideQuoteDataMapper $quoteViewService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showQuotePreviewData(WorldwideQuote $worldwideQuote, WorldwideQuoteDataMapper $quoteViewService): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);

        $resource = $quoteViewService->mapWorldwideQuotePreviewData($worldwideQuote);

        return response()->json(
            $resource,
            Response::HTTP_OK
        );
    }

    /**
     * Export the specified Worldwide Quote.
     *
     * @param WorldwideQuote $worldwideQuote
     * @param \App\Services\WorldwideQuote\WorldwideQuoteDataMapper $quoteViewService
     * @param WorldwideQuoteExporter $exporter
     * @return Response
     * @throws AuthorizationException
     */
    public function exportQuote(WorldwideQuote $worldwideQuote, WorldwideQuoteDataMapper $quoteViewService, WorldwideQuoteExporter $exporter): Response
    {
        $this->authorize('export', $worldwideQuote);

        $exportData = $quoteViewService->mapWorldwideQuotePreviewDataForExport($worldwideQuote);

        return $exporter->export($exportData);
    }

    /**
     * Show Quote Web preview. Dev only.
     *
     * @param WorldwideQuote $worldwideQuote
     * @param WorldwideQuoteDataMapper $dataMapper
     * @param \App\Services\WorldwideQuote\WorldwideQuoteExporter $exporter
     * @return View
     */
    public function showQuotePreview(WorldwideQuote $worldwideQuote, WorldwideQuoteDataMapper $dataMapper, WorldwideQuoteExporter $exporter): View
    {
//        $this->authorize('view', $worldwideQuote);

        $exportData = $dataMapper->mapWorldwideQuotePreviewData($worldwideQuote);

        return $exporter->buildView($exportData);
    }

    /**
     * Show Pack Quote applicable discounts.
     *
     * @param ShowPackQuoteApplicableDiscounts $request
     * @param WorldwideQuote $worldwideQuote
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showPackQuoteApplicableDiscounts(ShowPackQuoteApplicableDiscounts $request, WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);

        return response()->json(
            ApplicableDiscountCollection::make($request->getApplicableDiscounts()),
            Response::HTTP_OK
        );
    }

    /**
     * Show Price summary of contract quote after applied country margin & tax.
     *
     * @param ShowPriceSummaryOfContractQuoteAfterCountryMarginTax $request
     * @param WorldwideQuote $worldwideQuote
     * @param WorldwideQuoteCalc $quoteCalcService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showPriceSummaryOfContractQuoteAfterCountryMarginTax(ShowPriceSummaryOfContractQuoteAfterCountryMarginTax $request,
                                                                         WorldwideQuote $worldwideQuote,
                                                                         WorldwideQuoteCalc $quoteCalcService): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);

        $resource = $quoteCalcService->calculateContractQuotePriceSummaryAfterCountryMarginTax(
            $worldwideQuote,
            $request->getCountryMarginTaxCollection()
        );

        return response()->json(
            ContractQuotePriceSummary::make($resource),
            Response::HTTP_OK
        );
    }

    /**
     * Show Price summary of contract quote after applied discounts.
     *
     * @param ShowPriceSummaryOfContractQuoteAfterDiscounts $request
     * @param WorldwideQuote $worldwideQuote
     * @param \App\Services\WorldwideQuote\WorldwideQuoteCalc $quoteCalcService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showPriceSummaryOfContractQuoteAfterDiscounts(ShowPriceSummaryOfContractQuoteAfterDiscounts $request,
                                                                  WorldwideQuote $worldwideQuote,
                                                                  WorldwideQuoteCalc $quoteCalcService): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);

        $resource = $quoteCalcService->calculateContractQuotePriceSummaryAfterDiscounts(
            $worldwideQuote,
            $request->getDiscountCollection()
        );

        return response()->json(
            ContractQuotePriceSummary::make($resource),
            Response::HTTP_OK
        );
    }

    /**
     * Show Price summary of pack quote after applied country margin & tax.
     *
     * @param ShowPriceSummaryOfPackQuoteAfterCountryMarginTax $request
     * @param WorldwideQuote $worldwideQuote
     * @param \App\Services\WorldwideQuote\WorldwideQuoteCalc $quoteCalcService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showPriceSummaryOfPackQuoteAfterCountryMarginTax(ShowPriceSummaryOfPackQuoteAfterCountryMarginTax $request,
                                                                     WorldwideQuote $worldwideQuote,
                                                                     WorldwideQuoteCalc $quoteCalcService): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);

        $resource = $quoteCalcService->calculatePackQuotePriceSummaryAfterCountryMarginTax(
            $worldwideQuote,
            $request->getMarginTaxData()
        );

        return response()->json(
            PackQuotePriceSummary::make($resource),
            Response::HTTP_OK
        );
    }

    /**
     * Show Price summary of pack quote after applied discounts.
     *
     * @param ShowPriceSummaryOfPackQuoteAfterDiscounts $request
     * @param WorldwideQuote $worldwideQuote
     * @param WorldwideQuoteCalc $quoteCalcService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showPriceSummaryOfPackQuoteAfterDiscounts(ShowPriceSummaryOfPackQuoteAfterDiscounts $request,
                                                              WorldwideQuote $worldwideQuote,
                                                              WorldwideQuoteCalc $quoteCalcService): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);

        $resource = $quoteCalcService->calculatePackQuotePriceSummaryAfterDiscounts(
            $worldwideQuote,
            $request->getDiscountData()
        );

        return response()->json(
            PackQuotePriceSummary::make($resource),
            Response::HTTP_OK
        );
    }

    /**
     * Submit the specified Worldwide Quote.
     *
     * @param SubmitQuote $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideQuote $worldwideQuote
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function submitQuote(SubmitQuote $request,
                                WorldwideQuoteVersionGuard $versionGuard,
                                WorldwideQuote $worldwideQuote): Response
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $this->processor->processQuoteSubmission(
            $version,
            $request->getStage()
        );

        return response()->noContent();
    }

    /**
     * Draft the specified Worldwide Quote.
     *
     * @param DraftQuote $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideQuote $worldwideQuote
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function draftQuote(DraftQuote $request, WorldwideQuoteVersionGuard $versionGuard, WorldwideQuote $worldwideQuote): Response
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $this->processor->processQuoteDraft(
            $version,
            $request->getStage()
        );

        return response()->noContent();
    }

    /**
     * Unravel the specified Worldwide Quote.
     *
     * @param WorldwideQuote $worldwideQuote
     * @return Response
     * @throws AuthorizationException
     */
    public function unravelQuote(WorldwideQuote $worldwideQuote): Response
    {
        $this->authorize('unravel', $worldwideQuote);

        $this->processor->processQuoteUnravel($worldwideQuote);

        return response()->noContent();
    }

    /**
     * Mark the specified Worldwide Quote as active.
     *
     * @param WorldwideQuote $worldwideQuote
     * @return Response
     * @throws AuthorizationException
     */
    public function activateQuote(WorldwideQuote $worldwideQuote): Response
    {
        $this->authorize('changeStatus', $worldwideQuote);

        $this->processor->activateQuote($worldwideQuote);

        return response()->noContent();
    }

    /**
     * Mark the specified Worldwide Quote as inactive.
     *
     * @param WorldwideQuote $worldwideQuote
     * @return Response
     * @throws AuthorizationException
     */
    public function deactivateQuote(WorldwideQuote $worldwideQuote): Response
    {
        $this->authorize('changeStatus', $worldwideQuote);

        $this->processor->deactivateQuote($worldwideQuote);

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param WorldwideQuote $worldwideQuote
     * @return Response
     * @throws AuthorizationException
     */
    public function destroyQuote(WorldwideQuote $worldwideQuote): Response
    {
        $this->authorize('delete', $worldwideQuote);

        $this->processor->deleteQuote($worldwideQuote);

        return response()->noContent();
    }

    /**
     * Perform validation of the specified Quote and display the validation errors on fail.
     *
     * @param WorldwideQuote $worldwideQuote
     * @param WorldwideQuoteValidator $validator
     * @return WorldwideQuoteValidationResult
     * @throws AuthorizationException
     */
    public function validateQuote(WorldwideQuote $worldwideQuote, WorldwideQuoteValidator $validator): WorldwideQuoteValidationResult
    {
        $this->authorize('view', $worldwideQuote);

        return $validator->validateQuote($worldwideQuote);
    }

    /**
     * Mark the specified quote entity as 'dead'.
     *
     * @param MarkWorldwideQuoteAsDead $request
     * @param WorldwideQuote $worldwideQuote
     * @return Response
     * @throws AuthorizationException
     */
    public function markQuoteAsDead(MarkWorldwideQuoteAsDead $request, WorldwideQuote $worldwideQuote): Response
    {
        $this->authorize('changeStatus', $worldwideQuote);

        $this->processor->markQuoteAsDead($worldwideQuote, $request->getMarkQuoteAsDeadData());

        return response()->noContent();
    }

    /**
     * Mark the specified quote entity as 'alive'.
     *
     * @param WorldwideQuote $worldwideQuote
     * @return Response
     * @throws AuthorizationException
     */
    public function markQuoteAsAlive(WorldwideQuote $worldwideQuote): Response
    {
        $this->authorize('changeStatus', $worldwideQuote);

        $this->processor->markQuoteAsAlive($worldwideQuote);

        return response()->noContent();
    }

    /**
     * Perform replication of quote.
     *
     * @param Request $request
     * @param WorldwideQuote $worldwideQuote
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function replicateQuote(Request $request, WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('replicate', $worldwideQuote);

        $replicatedQuote = $this->processor->processQuoteReplication($worldwideQuote, $request->user());

        return response()->json(
            $replicatedQuote,
            Response::HTTP_CREATED
        );
    }
}
