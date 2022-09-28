<?php

namespace App\Http\Controllers\API\V1\WorldwideQuotes;

use App\Contracts\Services\ManagesExchangeRates as ExchangeRateService;
use App\Contracts\Services\ProcessesWorldwideQuoteAssetState;
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
use App\Http\Resources\{V1\Appointment\AppointmentListResource,
    V1\Discount\ApplicableDiscountCollection,
    V1\WorldwideQuote\ContractQuotePriceSummary,
    V1\WorldwideQuote\NewlyCreatedWorldwideQuoteVersion,
    V1\WorldwideQuote\PackQuotePriceSummary,
    V1\WorldwideQuote\WorldwideQuoteState};
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Queries\AppointmentQueries;
use App\Services\{WorldwideQuote\Calculation\WorldwideQuoteCalc,
    WorldwideQuote\CollectWorldwideQuoteFilesService,
    WorldwideQuote\Models\QuoteExportResult,
    WorldwideQuote\WorldwideQuoteDataMapper,
    WorldwideQuote\WorldwideQuoteExporter,
    WorldwideQuote\WorldwideQuoteValidator,
    WorldwideQuote\WorldwideQuoteVersionGuard};
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
     * @param InitQuote $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function initializeQuote(InitQuote $request): JsonResponse
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
            Response::HTTP_CREATED
        );
    }

    /**
     * Create a new version of Worldwide Quote.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Quote\WorldwideQuote $worldwideQuote
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function createVersionOfQuote(Request                    $request,
                                         WorldwideQuote             $worldwideQuote,
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
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param \App\Models\Quote\WorldwideQuote $worldwideQuote
     * @param \App\Models\Quote\WorldwideQuoteVersion $version
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function createVersionOfQuoteFromVersion(Request                    $request,
                                                    WorldwideQuoteVersionGuard $versionGuard,
                                                    WorldwideQuote             $worldwideQuote,
                                                    WorldwideQuoteVersion      $version): JsonResponse
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
     * @param \Illuminate\Http\Request $request
     * @param WorldwideQuote $worldwideQuote
     * @param WorldwideQuoteVersion $version
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function switchActiveVersionOfQuote(Request               $request,
                                               WorldwideQuote        $worldwideQuote,
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
     * @param \Illuminate\Http\Request $request
     * @param WorldwideQuote $worldwideQuote
     * @param WorldwideQuoteVersion $version
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroyQuoteVersion(Request               $request,
                                        WorldwideQuote        $worldwideQuote,
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
     * @param ProcessQuoteAddressesContacts $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param ProcessesWorldwideQuoteAssetState $assetProcessor
     * @param WorldwideQuote $worldwideQuote
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws \Throwable
     */
    public function processQuoteAddressesContactsStep(ProcessQuoteAddressesContacts     $request,
                                                      WorldwideQuoteVersionGuard        $versionGuard,
                                                      ProcessesWorldwideQuoteAssetState $assetProcessor,
                                                      WorldwideQuote                    $worldwideQuote): JsonResponse
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
     * @param UpdateQuoteImport $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideQuote $worldwideQuote
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function processQuoteImportStep(
        UpdateQuoteImport          $request,
        WorldwideQuoteVersionGuard $versionGuard,
        WorldwideQuote             $worldwideQuote
    ): JsonResponse
    {
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
     * @param ProcessQuoteAssetsReviewStep $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideQuote $worldwideQuote
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function processQuoteAssetsReviewStep(ProcessQuoteAssetsReviewStep $request,
                                                 WorldwideQuoteVersionGuard   $versionGuard,
                                                 WorldwideQuote               $worldwideQuote): JsonResponse
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
     * @param ProcessQuoteMarginStep $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideQuote $worldwideQuote
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function processQuoteMarginStep(ProcessQuoteMarginStep     $request,
                                           WorldwideQuoteVersionGuard $versionGuard,
                                           WorldwideQuote             $worldwideQuote): JsonResponse
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
     * @param ProcessQuoteDiscountStep $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideQuote $worldwideQuote
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function processQuoteDiscountStep(ProcessQuoteDiscountStep   $request,
                                             WorldwideQuoteVersionGuard $versionGuard,
                                             WorldwideQuote             $worldwideQuote): JsonResponse
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
     * @param ProcessQuoteDetails $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideQuote $worldwideQuote
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function processQuoteDetailsStep(ProcessQuoteDetails        $request,
                                            WorldwideQuoteVersionGuard $versionGuard,
                                            WorldwideQuote             $worldwideQuote): JsonResponse
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
     * @param ShowQuoteState $request
     * @param WorldwideQuote $worldwideQuote
     * @param ExchangeRateService $exchangeRateService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showQuoteState(ShowQuoteState      $request,
                                   WorldwideQuote      $worldwideQuote,
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
     * @param  WorldwideQuote  $worldwideQuote
     * @param  WorldwideQuoteDataMapper  $quoteViewService
     * @param  WorldwideQuoteExporter  $exporter
     * @return QuoteExportResult
     * @throws AuthorizationException
     */
    public function exportQuote(
        WorldwideQuote $worldwideQuote,
        WorldwideQuoteDataMapper $quoteViewService,
        WorldwideQuoteExporter $exporter
    ): QuoteExportResult
    {
        $this->authorize('export', $worldwideQuote);

        $exportData = $quoteViewService->mapWorldwideQuotePreviewDataForExport($worldwideQuote);

        return $exporter->export($exportData, $worldwideQuote);
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
            ApplicableDiscountCollection::make($request->getApplicableDiscounts($worldwideQuote)),
            Response::HTTP_OK
        );
    }

    /**
     * Show Price summary of contract quote after applied country margin & tax.
     *
     * @param ShowPriceSummaryOfContractQuoteAfterCountryMarginTax $request
     * @param WorldwideQuote $worldwideQuote
     * @param \App\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc $quoteCalcService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showPriceSummaryOfContractQuoteAfterCountryMarginTax(ShowPriceSummaryOfContractQuoteAfterCountryMarginTax $request,
                                                                         WorldwideQuote                                       $worldwideQuote,
                                                                         WorldwideQuoteCalc                                   $quoteCalcService): JsonResponse
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
     * @param \App\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc $quoteCalcService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showPriceSummaryOfContractQuoteAfterDiscounts(ShowPriceSummaryOfContractQuoteAfterDiscounts $request,
                                                                  WorldwideQuote                                $worldwideQuote,
                                                                  WorldwideQuoteCalc                            $quoteCalcService): JsonResponse
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
     * @param \App\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc $quoteCalcService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showPriceSummaryOfPackQuoteAfterCountryMarginTax(ShowPriceSummaryOfPackQuoteAfterCountryMarginTax $request,
                                                                     WorldwideQuote                                   $worldwideQuote,
                                                                     WorldwideQuoteCalc                               $quoteCalcService): JsonResponse
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
     * @param \App\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc $quoteCalcService
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showPriceSummaryOfPackQuoteAfterDiscounts(ShowPriceSummaryOfPackQuoteAfterDiscounts $request,
                                                              WorldwideQuote                            $worldwideQuote,
                                                              WorldwideQuoteCalc                        $quoteCalcService): JsonResponse
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
    public function submitQuote(SubmitQuote                $request,
                                WorldwideQuoteVersionGuard $versionGuard,
                                WorldwideQuote             $worldwideQuote): Response
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
     * @param \Illuminate\Http\Request $request
     * @param WorldwideQuote $worldwideQuote
     * @return Response
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
     * @param \Illuminate\Http\Request $request
     * @param WorldwideQuote $worldwideQuote
     * @return Response
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
     * @param \Illuminate\Http\Request $request
     * @param WorldwideQuote $worldwideQuote
     * @return Response
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
     * @param \Illuminate\Http\Request $request
     * @param WorldwideQuote $worldwideQuote
     * @return Response
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

        $this->processor
            ->setActingUser($request->user())
            ->markQuoteAsDead($worldwideQuote, $request->getMarkQuoteAsDeadData());

        return response()->noContent();
    }

    /**
     * Mark the specified quote entity as 'alive'.
     *
     * @param \Illuminate\Http\Request $request
     * @param WorldwideQuote $worldwideQuote
     * @return Response
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
     * @param Request $request
     * @param WorldwideQuote $worldwideQuote
     * @return JsonResponse
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
     * @param Request $request
     * @param AppointmentQueries $appointmentQueries
     * @param WorldwideQuote $worldwideQuote
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function showAppointmentsOfQuote(Request            $request,
                                            AppointmentQueries $appointmentQueries,
                                            WorldwideQuote     $worldwideQuote): AnonymousResourceCollection
    {
        $this->authorize('view', $worldwideQuote);

        $resource = $appointmentQueries->listAppointmentsLinkedToQuery($worldwideQuote, $request)->get();

        return AppointmentListResource::collection($resource);
    }
}
