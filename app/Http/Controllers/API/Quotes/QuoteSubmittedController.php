<?php

namespace App\Http\Controllers\API\Quotes;

use App\Contracts\{Repositories\Quote\QuoteSubmittedRepositoryInterface as Repository, Services\ContractState};
use App\Contracts\Services\QuoteState;
use App\Contracts\Services\QuoteView;
use App\Http\Controllers\Controller;
use App\Http\Requests\Quote\Copy;
use App\Http\Requests\Quote\CreateQuoteContractRequest;
use App\Http\Resources\ContractVersionResource;
use App\Models\{Quote\Quote, Template\ContractTemplate};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class QuoteSubmittedController extends Controller
{
    protected Repository $repository;

    protected ContractState $contractProcessor;

    public function __construct(Repository $repository, ContractState $contractProcessor)
    {
        $this->repository = $repository;
        $this->contractProcessor = $contractProcessor;
        $this->authorizeResource(Quote::class, 'submitted');
    }

    /**
     * Display a listing of the Submitted Quotes.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(
            request()->filled('search')
                ? $this->repository->search(request('search'))
                : $this->repository->all()
        );
    }

    /**
     * Retrieve the specified Submitted Quote.
     *
     * @param Quote $submitted
     * @return JsonResponse
     */
    public function show(Quote $submitted): JsonResponse
    {
        return response()->json(
            $this->repository->find($submitted->getKey())
        );
    }

    /**
     * Remove the specified Submitted Quote
     *
     * @param Quote $submitted
     * @return JsonResponse
     */
    public function destroy(Quote $submitted): JsonResponse
    {
        return response()->json(
            $this->repository->delete($submitted->getKey())
        );
    }

    /**
     * Activate the specified Submitted Quote
     *
     * @param Quote $submitted
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function activate(Quote $submitted): JsonResponse
    {
        $this->authorize('activate', $submitted);

        return response()->json(
            $this->repository->activate($submitted->getKey())
        );
    }

    /**
     * Deactivate the specified Submitted Quote
     *
     * @param Quote $submitted
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function deactivate(Quote $submitted): JsonResponse
    {
        $this->authorize('update', $submitted);

        return response()->json(
            $this->repository->deactivate($submitted->getKey())
        );
    }

    /**
     * Create copy of the specified Submitted Quote
     *
     * @param Copy $request
     * @param Quote $submitted
     * @param QuoteState $quoteProcessor
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function copy(Copy $request, Quote $submitted, QuoteState $quoteProcessor): JsonResponse
    {
        $this->authorize('copy', $submitted);

        $quoteProcessor->replicateQuote($submitted);

        return response()->json(
            true
        );
    }

    /**
     * Back a specified Quote to drafted.
     *
     * @param Quote $submitted
     * @param QuoteState $quoteProcessor
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function unravelQuote(Quote $submitted, QuoteState $quoteProcessor): JsonResponse
    {
        $this->authorize('unravel', $submitted);

        $quoteProcessor->processQuoteUnravel($submitted);

        return response()->json(
            true
        );
    }

    /**
     * Create a new Contract based on the given Quote.
     *
     * @param CreateQuoteContractRequest $request
     * @param Quote $submitted
     * @return JsonResponse
     */
    public function createContract(CreateQuoteContractRequest $request, Quote $submitted): JsonResponse
    {
        $resource = $this->contractProcessor->createFromQuote($submitted, $request->validated());

        return response()->json(
            ContractVersionResource::make($resource)
        );
    }

    /**
     * Export the specified Quote as PDF.
     *
     * @param Quote $submitted
     * @param QuoteView $quoteViewService
     * @return Response
     * @throws AuthorizationException
     */
    public function exportQuoteToPdf(Quote $submitted, QuoteView $quoteViewService): Response
    {
        $this->authorize('downloadPdf', $submitted);

        return $quoteViewService->export($submitted->activeVersionOrCurrent, QT_TYPE_QUOTE);
    }

    /**
     * Export the specified Quote as Contract PDF.
     *
     * @param Quote $submitted
     * @param QuoteView $quoteViewService
     * @return Response
     * @throws AuthorizationException
     */
    public function exportContractOfQuoteToPdf(Quote $submitted, QuoteView $quoteViewService)
    {
        $this->authorize('downloadContractPdf', $submitted);
        return $quoteViewService->export($submitted->activeVersionOrCurrent, QT_TYPE_CONTRACT);
    }

    /**
     * Show contract of quote web preview. Dev only.
     *
     * @param Quote $submitted
     * @param QuoteView $quoteViewService
     * @return View
     */
    public function showContractOfQuotePreview(Quote $submitted, QuoteView $quoteViewService): View
    {
        return $quoteViewService->buildView($submitted->activeVersionOrCurrent, QT_TYPE_CONTRACT);
    }

    /**
     * Set the specified Contract Template for the Quote.
     *
     * @param Quote $submitted
     * @param ContractTemplate $template
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function setContractTemplate(Quote $submitted, ContractTemplate $template): JsonResponse
    {
        $this->authorize('update', $submitted);

        return response()->json(
            $this->repository->setContractTemplate($submitted->getKey(), $template->getKey())
        );
    }
}
