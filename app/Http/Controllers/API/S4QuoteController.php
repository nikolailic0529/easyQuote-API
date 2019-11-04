<?php namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\QuoteSubmittedRepositoryInterface as QuoteSubmittedRepository;

class S4QuoteController extends Controller
{
    protected $quote;

    public function __construct(QuoteSubmittedRepository $quote)
    {
        $this->quote = $quote;
    }

    /**
     * Handle the incoming request.
     *
     * @param  string  $rfq
     * @return \Illuminate\Http\Response
     */
    public function __invoke(string $rfq)
    {
        return response()->json(
            $this->quote->rfq($rfq)
        );
    }
}
