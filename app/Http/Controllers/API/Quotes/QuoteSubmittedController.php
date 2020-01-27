<?php

namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\QuoteSubmittedRepositoryInterface as Repository;
use App\Models\{
    Quote\Quote,
    QuoteTemplate\ContractTemplate
};

class QuoteSubmittedController extends Controller
{
    /** @var \App\Contracts\Repositories\Quote\QuoteSubmittedRepositoryInterface */
    protected $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
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
     * @param Quote $submitted
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
        $this->authorize('update', $submitted);

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
     * @param Quote $submitted
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
     * @param Quote $submitted
     * @return \Illuminate\Http\Response
     */
    public function unSubmit(Quote $submitted)
    {
        $this->authorize('update', $submitted);

        return response()->json(
            $this->repository->unSubmit($submitted->id)
        );
    }

    /**
     * Export the specified Quote as PDF.
     *
     * @param Quote $submitted
     * @return \Illuminate\Http\Response
     */
    public function pdf(Quote $submitted)
    {
        $this->authorize('download_pdf', $submitted);

        return $this->repository->exportPdf($submitted);
    }

    /**
     * Set the specified Contract Template for the Quote.
     *
     * @param Quote $submitted
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
