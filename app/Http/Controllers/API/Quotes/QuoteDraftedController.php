<?php

namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\QuoteDraftedRepositoryInterface as QuoteRepository;
use App\Models\{
    Quote\Quote,
    Quote\QuoteVersion
};

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
        return response()->json(
            request()->filled('search')
                ? $this->quote->search(request('search'))
                : $this->quote->all()
        );
    }

    public function show(Quote $drafted)
    {
        return response()->json(
            $this->quote->find($drafted->id)
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
            $this->quote->delete($drafted->id)
        );
    }

    /**
     * Remove the specified Drafted Quote Version.
     *
     * @param  QuoteVersion $version
     * @return \Illuminate\Http\Response
     */
    public function destroyVersion(QuoteVersion $version, Quote $quote)
    {
        $this->authorize('deleteVersion', [$version->quote, $version]);

        return response()->json(
            $this->quote->deleteVersion($version->id)
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
            $this->quote->activate($drafted->id)
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
            $this->quote->deactivate($drafted->id)
        );
    }
}
