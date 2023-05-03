<?php

namespace App\Domain\Rescue\Controllers\V1;

use App\Domain\Rescue\Contracts\QuoteDraftedRepositoryInterface as QuoteRepository;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Rescue\Models\QuoteVersion;
use App\Foundation\Http\Controller;

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
     * Remove the specified Drafted Quote.
     *
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
     * Activate the specified Drafted Quote.
     *
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
     * Deactivate the specified Drafted Quote.
     *
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
