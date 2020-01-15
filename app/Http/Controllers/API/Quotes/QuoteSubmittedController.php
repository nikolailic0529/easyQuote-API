<?php

namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\QuoteSubmittedRepositoryInterface as QuoteRepository;
use App\Models\Quote\Quote;

class QuoteSubmittedController extends Controller
{
    protected $quote;

    public function __construct(QuoteRepository $quote)
    {
        $this->quote = $quote;
        // $this->authorizeResource(Quote::class, 'submitted');
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
                ? $this->quote->search(request('search'))
                : $this->quote->all()
        );
    }

    public function show(Quote $submitted)
    {
        return response()->json(
            $this->quote->find($submitted->id)
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
            $this->quote->delete($submitted->id)
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
            $this->quote->activate($submitted->id)
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
            $this->quote->deactivate($submitted->id)
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
            $this->quote->copy($submitted)
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
            $this->quote->unSubmit($submitted->id)
        );
    }

    /**
     * Retrieve if exists Generated PDF file by RFQ number.
     *
     * @param string $rfq
     * @return \Illuminate\Http\Response
     */
    public function pdf(string $rfq)
    {
        $this->authorize('download_pdf', Quote::class);

        return $this->quote->pdf($rfq);
    }
}
