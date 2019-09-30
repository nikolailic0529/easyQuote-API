<?php namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\QuoteRepositoryInterface as QuoteRepository;

class QuoteDraftedController extends Controller
{
    protected $quote;

    public function __construct(QuoteRepository $quote)
    {
        $this->quote = $quote;
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

    public function show(string $quote)
    {
        return response()->json(
            $this->quote->getDrafted($quote)
        );
    }

    /**
     * Remove the specified Drafted Quote
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        return response()->json(
            $this->quote->deleteDrafted($id)
        );
    }

    /**
     * Activate the specified Drafted Quote
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function activate(string $id)
    {
        return response()->json(
            $this->quote->activateDrafted($id)
        );
    }

    /**
     * Deactivate the specified Drafted Quote
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function deactivate(string $id)
    {
        return response()->json(
            $this->quote->deactivateDrafted($id)
        );
    }
}
