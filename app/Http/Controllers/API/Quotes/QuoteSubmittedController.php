<?php

namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\{
    Contract\ContractStateRepositoryInterface as Contracts,
    Quote\QuoteSubmittedRepositoryInterface as Repository
};
use App\Http\Requests\Quote\CreateQuoteContractRequest;
use App\Http\Resources\ContractVersionResource;
use App\Models\{
    Quote\Quote,
    QuoteTemplate\ContractTemplate
};

class QuoteSubmittedController extends Controller
{
    protected Repository $repository;

    protected Contracts $contracts;

    public function __construct(Repository $repository, Contracts $contracts)
    {
        $this->repository = $repository;
        $this->contracts = $contracts;
        $this->authorizeResource(Quote::class, 'submitted');
    }

    /**
     * Display a listing of the Submitted Quotes.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
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
     * @param \App\Models\Quote\Quote $submitted
     * @return \Illuminate\Http\Response
     */
    public function show(Quote $submitted)
    {
        return response()->json(
            $this->repository->find($submitted->id)
        );
    }

    /**
     * Remove the specified Submitted Quote
     *
     * @param  Quote $submitted
     * @return \Illuminate\Http\Response
     */
    public function destroy(Quote $submitted)
    {
        return response()->json(
            $this->repository->delete($submitted->id)
        );
    }

    /**
     * Activate the specified Submitted Quote
     *
     * @param  Quote $submitted
     * @return \Illuminate\Http\Response
     */
    public function activate(Quote $submitted)
    {
        $this->authorize('activate', $submitted);

        return response()->json(
            $this->repository->activate($submitted->id)
        );
    }

    /**
     * Deactivate the specified Submitted Quote
     *
     * @param  Quote $submitted
     * @return \Illuminate\Http\Response
     */
    public function deactivate(Quote $submitted)
    {
        $this->authorize('update', $submitted);

        return response()->json(
            $this->repository->deactivate($submitted->id)
        );
    }

    /**
     * Create copy of the specified Submitted Quote
     *
     * @param \App\Models\Quote\Quote $submitted
     * @return \Illuminate\Http\Response
     */
    public function copy(Quote $submitted)
    {
        $this->authorize('copy', $submitted);

        return response()->json(
            $this->repository->copy($submitted)
        );
    }

    /**
     * Back a specified Quote to drafted.
     *
     * @param \App\Models\Quote\Quote $submitted
     * @return \Illuminate\Http\Response
     */
    public function unSubmit(Quote $submitted)
    {
        $this->authorize('unravel', $submitted);

        return response()->json(
            $this->repository->unSubmit($submitted->id)
        );
    }

    /**
     * Create a new Contract based on the given Quote.
     *
     * @param \App\Http\Requests\Quote\CreateQuoteContractRequest $request
     * @param \App\Models\Quote\Quote $submitted
     * @return \Illuminate\Http\Response
     */
    public function createContract(CreateQuoteContractRequest $request, Quote $submitted)
    {
        $this->authorize('createContract', $submitted);

        $resource = $this->contracts->createFromQuote($submitted, $request->validated());

        return response()->json(
            ContractVersionResource::make($resource)
        );
    }

    /**
     * Export the specified Quote as PDF.
     *
     * @param \App\Models\Quote\Quote $submitted
     * @return \Illuminate\Http\Response
     */
    public function pdf(Quote $submitted)
    {
        $this->authorize('downloadPdf', $submitted);

        return $this->repository->exportPdf($submitted, QT_TYPE_QUOTE);
    }

    /**
     * Export the specified Quote as Contract PDF.
     *
     * @param \App\Models\Quote\Quote $submitted
     * @return \Illuminate\Http\Response
     */
    public function contractPdf(Quote $submitted)
    {
        $this->authorize('downloadContractPdf', $submitted);

        return $this->repository->exportPdf($submitted, QT_TYPE_CONTRACT);
    }

    /**
     * Set the specified Contract Template for the Quote.
     *
     * @param \App\Models\Quote\Quote $submitted
     * @param ContractTemplate $template
     * @return \Illuminate\Http\Response
     */
    public function setContractTemplate(Quote $submitted, ContractTemplate $template)
    {
        $this->authorize('update', $submitted);

        return response()->json(
            $this->repository->setContractTemplate($submitted->id, $template->id)
        );
    }
}
