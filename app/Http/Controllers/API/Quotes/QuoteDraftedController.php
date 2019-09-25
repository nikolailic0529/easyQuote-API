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
     * Display a listing of the resource.
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
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return response()->json(
            $this->quote->deleteDrafted($id)
        );
    }
}
