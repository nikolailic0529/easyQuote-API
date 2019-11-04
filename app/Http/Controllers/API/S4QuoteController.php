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
    public function show(string $rfq)
    {
        return response()->json(
            $this->quote->rfq($rfq)
        );
    }

    public function price(string $rfq)
    {
        return response()->download(
            $this->quote->price($rfq)
        );
    }

    public function schedule(string $rfq)
    {
        return response()->download(
            $this->quote->schedule($rfq)
        );
    }

    public function pdf(string $rfq)
    {
        return response()->download(
            $this->quote->pdf($rfq)
        );
    }
}
