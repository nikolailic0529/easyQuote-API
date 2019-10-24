<?php namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\QuoteRepositoryInterface as QuoteRepository;
use App\Models\Quote\Quote;

class QuoteSubmittedController extends Controller
{
    protected $quote;

    public function __construct(QuoteRepository $quote)
    {
        $this->quote = $quote;
        $this->authorizeResource(Quote::class, 'submitted');
    }

    /**
     * Display a listing of the Submitted Quotes.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(request()->filled('search')) {
            return response()->json(
                $this->quote->searchSubmitted(request('search'))
            );
        }

        return response()->json(
            $this->quote->allSubmitted()
        );
    }

    public function show(Quote $submitted)
    {
        return response()->json(
            $this->quote->getSubmitted($quote->id)
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
            $this->quote->deleteSubmitted($submitted->id)
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
            $this->quote->activateSubmitted($submitted->id)
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
            $this->quote->deactivateSubmitted($submitted->id)
        );
    }

    /**
     * Create copy of the specified Submitted Quote
     *
     * @param Quote $submitted
     * @return void
     */
    public function copy(Quote $submitted)
    {
        $this->authorize('copy', $submitted);

        return response()->json(
            $this->quote->copy($submitted->id)
        );
    }
}