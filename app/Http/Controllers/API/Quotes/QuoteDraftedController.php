<?php namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\QuoteRepositoryInterface as QuoteRepository;
use App\Models\Quote\Quote;

class QuoteDraftedController extends Controller
{
    protected $quote;

    public function __construct(QuoteRepository $quote)
    {
        $this->quote = $quote;
        $this->authorizeResource(Quote::class, 'drafted');
    }

    /**
     * Display a listing of the Drafted Quotes.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(request()->filled('search')) {
            return response()->json(
                $this->quote->searchDrafted(request('search'))
            );
        }

        return response()->json(
            $this->quote->allDrafted()
        );
    }

    public function show(Quote $drafted)
    {
        return response()->json(
            $this->quote->getDrafted($drafted->id)
        );
    }

    /**
     * Remove the specified Drafted Quote
     *
     * @param  Quote $drafted
     * @return \Illuminate\Http\Response
     */
    public function destroy(Quote $drafted)
    {
        return response()->json(
            $this->quote->deleteDrafted($drafted->id)
        );
    }

    /**
     * Activate the specified Drafted Quote
     *
     * @param  Quote $drafted
     * @return \Illuminate\Http\Response
     */
    public function activate(Quote $drafted)
    {
        $this->authorize('update', $drafted);

        return response()->json(
            $this->quote->activateDrafted($drafted->id)
        );
    }

    /**
     * Deactivate the specified Drafted Quote
     *
     * @param  Quote $drafted
     * @return \Illuminate\Http\Response
     */
    public function deactivate(Quote $drafted)
    {
        $this->authorize('update', $drafted);

        return response()->json(
            $this->quote->deactivateDrafted($drafted->id)
        );
    }
}
