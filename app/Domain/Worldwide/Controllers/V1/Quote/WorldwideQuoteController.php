<?php

namespace App\Domain\Worldwide\Controllers\V1\Quote;

use App\Domain\Appointment\Queries\AppointmentQueries;
use App\Domain\Appointment\Resources\V1\AppointmentListResource;
use App\Domain\Discount\Resources\V1\ApplicableDiscountCollection;
use App\Domain\ExchangeRate\Contracts\ManagesExchangeRates as ExchangeRateService;
use App\Domain\Worldwide\Contracts\ProcessesWorldwideQuoteAssetState;
use App\Domain\Worldwide\Contracts\ProcessesWorldwideQuoteState;
use App\Domain\Worldwide\DataTransferObjects\Quote\WorldwideQuoteValidationResult;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use App\Domain\Worldwide\Requests\Quote\DraftQuoteRequest;
use App\Domain\Worldwide\Requests\Quote\InitQuoteRequest;
use App\Domain\Worldwide\Requests\Quote\MarkWorldwideQuoteAsDeadRequest;
use App\Domain\Worldwide\Requests\Quote\ProcessQuoteAddressesContactsRequest;
use App\Domain\Worldwide\Requests\Quote\ProcessQuoteAssetsReviewStepRequest;
use App\Domain\Worldwide\Requests\Quote\ProcessQuoteDetailsRequest;
use App\Domain\Worldwide\Requests\Quote\ProcessQuoteDiscountStepRequest;
use App\Domain\Worldwide\Requests\Quote\ProcessQuoteMarginStepRequest;
use App\Domain\Worldwide\Requests\Quote\ShowPackQuoteApplicableDiscountsRequest;
use App\Domain\Worldwide\Requests\Quote\ShowPriceSummaryOfContractQuoteAfterCountryMarginTaxRequest;
use App\Domain\Worldwide\Requests\Quote\ShowPriceSummaryOfContractQuoteAfterDiscountsRequest;
use App\Domain\Worldwide\Requests\Quote\ShowPriceSummaryOfPackQuoteAfterCountryMarginTaxRequest;
use App\Domain\Worldwide\Requests\Quote\ShowPriceSummaryOfPackQuoteAfterDiscountsRequest;
use App\Domain\Worldwide\Requests\Quote\ShowQuoteStateRequest;
use App\Domain\Worldwide\Requests\Quote\SubmitQuoteRequest;
use App\Domain\Worldwide\Requests\Quote\UpdateQuoteImportRequest;
use App\Domain\Worldwide\Resources\V1\Quote\ContractQuotePriceSummary;
use App\Domain\Worldwide\Resources\V1\Quote\NewlyCreatedWorldwideQuoteVersion;
use App\Domain\Worldwide\Resources\V1\Quote\PackQuotePriceSummary;
use App\Domain\Worldwide\Resources\V1\Quote\WorldwideQuoteState;
use App\Domain\Worldwide\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc;
use App\Domain\Worldwide\Services\WorldwideQuote\CollectWorldwideQuoteFilesService;
use App\Domain\Worldwide\Services\WorldwideQuote\Models\QuoteExportResult;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteDataMapper;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteExporter;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteValidator;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteVersionGuard;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
     * @throws AuthorizationException
     */
    public function initializeQuote(InitQuoteRequest $request): JsonResponse
    {
        $this->authorize('create', WorldwideQuote::class);

        $worldwideQuote = $this->processor
            ->setActingUser($request->user())
            ->initializeQuote(
                $request->getStage()
            );

        $resource = WorldwideQuoteState::make($worldwideQuote);

        return response()->json(
            $resource,
            status: Response::HTTP_CREATED,
            options: JSON_PRESERVE_ZERO_FRACTION
        );
    }

    /**
     * Create a new version of Worldwide Quote.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function createVersionOfQuote(Request $request,
                                         WorldwideQuote $worldwideQuote,
                                         WorldwideQuoteVersionGuard $versionGuard): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->performQuoteVersioning($worldwideQuote, $request->user());

        return response()->json(
            NewlyCreatedWorldwideQuoteVersion::make($version),
            Response::HTTP_CREATED
        );
    }

    /**
     * Create a new version of Worldwide Quote from the specified version.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function createVersionOfQuoteFromVersion(Request $request,
                                                    WorldwideQuoteVersionGuard $versionGuard,
                                                    WorldwideQuote $worldwideQuote,
                                                    WorldwideQuoteVersion $version): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->performQuoteVersioningFromVersion($worldwideQuote, $version, $request->user());

        return response()->json(
            NewlyCreatedWorldwideQuoteVersion::make($version),
            Response::HTTP_CREATED
        );
    }

    /**
     * Switch active version of Worldwide Quote.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function switchActiveVersionOfQuote(Request $request,
                                               WorldwideQuote $worldwideQuote,
                                               WorldwideQuoteVersion $version): Response
    {
        $this->authorize('update', $worldwideQuote);

        $this->processor
            ->setActingUser($request->user())
            ->switchActiveVersionOfQuote($worldwideQuote, $version);

        return response()->noContent();
    }

    /**
     * Delete inactive version of Worldwide Quote.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroyQuoteVersion(Request $request,
                                        WorldwideQuote $worldwideQuote,
                                        WorldwideQuoteVersion $version): Response
    {
        $this->authorize('deleteVersion', [$worldwideQuote, $version]);

        $this->processor
            ->setActingUser($request->user())
            ->deleteVersionOfQuote($worldwideQuote, $version);

        return response()->noContent();
    }

    /**
     * Process Worldwide Quote addresses & contacts step.
     *
     * @throws AuthorizationException
     * @throws \Throwable
     */
    public function processQuoteAddressesContactsStep(ProcessQuoteAddressesContactsRequest $request,
                                                      WorldwideQuoteVersionGuard $versionGuard,
                                                      ProcessesWorldwideQuoteAssetState $assetProcessor,
                                                      WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $resource = $this->processor
            ->setActingUser($request->user())
            ->processQuoteSetupStep(
                $version,
                $request->getStage()
            );

        $assetProcessor->recalculateExchangeRateOfQuoteAssets(
            $version,
        );

        return response()->json(
            $resource,
            Response::HTTP_OK
        );
    }

    /**
     * Process Worldwide Quote import step.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function processQuoteImportStep(
        UpdateQuoteImportRequest $request,
        WorldwideQuoteVersionGuard $versionGuard,
        WorldwideQuote $worldwideQuote
    ): JsonResponse {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $resource = $this->processor
            ->setActingUser($request->user())
            ->processQuoteImportStep(
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function processQuoteAssetsReviewStep(ProcessQuoteAssetsReviewStepRequest $request,
                                                 WorldwideQuoteVersionGuard $versionGuard,
                                                 WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $resource = $this->processor
            ->setActingUser($request->user())
            ->processQuoteAssetsReviewStep(
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function processQuoteMarginStep(ProcessQuoteMarginStepRequest $request,
                                           WorldwideQuoteVersionGuard $versionGuard,
                                           WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $resource = $this->processor
            ->setActingUser($request->user())
            ->processPackQuoteMarginStep(
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function processQuoteDiscountStep(ProcessQuoteDiscountStepRequest $request,
                                             WorldwideQuoteVersionGuard $versionGuard,
                                             WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $resource = $this->processor
            ->setActingUser($request->user())
            ->processPackQuoteDiscountStep(
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function processQuoteDetailsStep(ProcessQuoteDetailsRequest $request,
                                            WorldwideQuoteVersionGuard $versionGuard,
                                            WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $resource = $this->processor
            ->setActingUser($request->user())
            ->processPackQuoteDetailsStep(
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
     * @throws AuthorizationException
     */
    public function showQuoteState(ShowQuoteStateRequest $request,
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
            status: Response::HTTP_OK,
            options: JSON_PRESERVE_ZERO_FRACTION
        );
    }

    /**
     * Download existing Distributor Files from Worldwide Quote.
     *
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
     * @throws AuthorizationException
     */
    public function exportQuote(
        WorldwideQuote $worldwideQuote,
        WorldwideQuoteDataMapper $quoteViewService,
        WorldwideQuoteExporter $exporter
    ): QuoteExportResult {
        $this->authorize('export', $worldwideQuote);

        $exportData = $quoteViewService->mapWorldwideQuotePreviewDataForExport($worldwideQuote);

        return $exporter->export($exportData, $worldwideQuote);
    }

    /**
     * Show Quote Web preview. Dev only.
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
     * @throws AuthorizationException
     */
    public function showPackQuoteApplicableDiscounts(ShowPackQuoteApplicableDiscountsRequest $request, WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);

        return response()->json(
            ApplicableDiscountCollection::make($request->getApplicableDiscounts($worldwideQuote)),
            Response::HTTP_OK
        );
    }

    /**
     * Show Price summary of contract quote after applied country margin & tax.
     *
     * @throws AuthorizationException
     */
    public function showPriceSummaryOfContractQuoteAfterCountryMarginTax(ShowPriceSummaryOfContractQuoteAfterCountryMarginTaxRequest $request,
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
     * @throws AuthorizationException
     */
    public function showPriceSummaryOfContractQuoteAfterDiscounts(ShowPriceSummaryOfContractQuoteAfterDiscountsRequest $request,
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
     * @throws AuthorizationException
     */
    public function showPriceSummaryOfPackQuoteAfterCountryMarginTax(ShowPriceSummaryOfPackQuoteAfterCountryMarginTaxRequest $request,
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
     * @throws AuthorizationException
     */
    public function showPriceSummaryOfPackQuoteAfterDiscounts(ShowPriceSummaryOfPackQuoteAfterDiscountsRequest $request,
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function submitQuote(SubmitQuoteRequest $request,
                                WorldwideQuoteVersionGuard $versionGuard,
                                WorldwideQuote $worldwideQuote): Response
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $this->processor
            ->setActingUser($request->user())
            ->processQuoteSubmission(
                $version,
                $request->getStage()
            );

        return response()->noContent();
    }

    /**
     * Draft the specified Worldwide Quote.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function draftQuote(DraftQuoteRequest $request, WorldwideQuoteVersionGuard $versionGuard, WorldwideQuote $worldwideQuote): Response
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $this->processor
            ->setActingUser($request->user())
            ->processQuoteDraft(
                $version,
                $request->getStage()
            );

        return response()->noContent();
    }

    /**
     * Unravel the specified Worldwide Quote.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function unravelQuote(Request $request, WorldwideQuote $worldwideQuote): Response
    {
        $this->authorize('unravel', $worldwideQuote);

        $this->processor
            ->setActingUser($request->user())
            ->processQuoteUnravel($worldwideQuote);

        return response()->noContent();
    }

    /**
     * Mark the specified Worldwide Quote as active.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function activateQuote(Request $request, WorldwideQuote $worldwideQuote): Response
    {
        $this->authorize('changeStatus', $worldwideQuote);

        $this->processor
            ->setActingUser($request->user())
            ->activateQuote($worldwideQuote);

        return response()->noContent();
    }

    /**
     * Mark the specified Worldwide Quote as inactive.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deactivateQuote(Request $request, WorldwideQuote $worldwideQuote): Response
    {
        $this->authorize('changeStatus', $worldwideQuote);

        $this->processor
            ->setActingUser($request->user())
            ->deactivateQuote($worldwideQuote);

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroyQuote(Request $request, WorldwideQuote $worldwideQuote): Response
    {
        $this->authorize('delete', $worldwideQuote);

        $this->processor
            ->setActingUser($request->user())
            ->deleteQuote($worldwideQuote);

        return response()->noContent();
    }

    /**
     * Perform validation of the specified Quote and display the validation errors on fail.
     *
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
     * @throws AuthorizationException
     */
    public function markQuoteAsDead(MarkWorldwideQuoteAsDeadRequest $request, WorldwideQuote $worldwideQuote): Response
    {
        $this->authorize('changeStatus', $worldwideQuote);

        $this->processor
            ->setActingUser($request->user())
            ->markQuoteAsDead($worldwideQuote, $request->getMarkQuoteAsDeadData());

        return response()->noContent();
    }

    /**
     * Mark the specified quote entity as 'alive'.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function markQuoteAsAlive(Request $request, WorldwideQuote $worldwideQuote): Response
    {
        $this->authorize('changeStatus', $worldwideQuote);

        $this->processor
            ->setActingUser($request->user())
            ->markQuoteAsAlive($worldwideQuote);

        return response()->noContent();
    }

    /**
     * Perform replication of quote.
     *
     * @throws AuthorizationException
     */
    public function replicateQuote(Request $request, WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('replicate', $worldwideQuote);

        $replicatedQuote = $this->processor
            ->setActingUser($request->user())
            ->processQuoteReplication($worldwideQuote, $request->user());

        return response()->json(
            $replicatedQuote,
            Response::HTTP_CREATED
        );
    }

    /**
     * List appointments linked to quote.
     *
     * @throws AuthorizationException
     */
    public function showAppointmentsOfQuote(Request $request,
                                            AppointmentQueries $appointmentQueries,
                                            WorldwideQuote $worldwideQuote): AnonymousResourceCollection
    {
        $this->authorize('view', $worldwideQuote);

        $resource = $appointmentQueries->listAppointmentsLinkedToQuery($worldwideQuote, $request)->get();

        return AppointmentListResource::collection($resource);
    }
}
