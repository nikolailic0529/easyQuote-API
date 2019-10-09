<?php namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\QuoteRepositoryInterface as QuoteRepository;

class QuoteSubmittedController extends Controller
{
    protected $quote;

    public function __construct(QuoteRepository $quote)
    {
        $this->quote = $quote;
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

    public function show(string $quote)
    {
        return response()->json(
            $this->quote->getSubmitted($quote)
        );
    }

    /**
     * Remove the specified Submitted Quote
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        return response()->json(
            $this->quote->deleteSubmitted($id)
        );
    }

    /**
     * Activate the specified Submitted Quote
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function activate(string $id)
    {
        return response()->json(
            $this->quote->activateSubmitted($id)
        );
    }

    /**
     * Deactivate the specified Submitted Quote
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function deactivate(string $id)
    {
        return response()->json(
            $this->quote->deactivateSubmitted($id)
        );
    }

    /**
     * Create copy of the specified Submitted Quote
     *
     * @param string $id
     * @return void
     */
    public function copy(string $id)
    {
        return response()->json(
            $this->quote->copy($id)
        );
    }
}
