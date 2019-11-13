<?php

namespace App\Http\Controllers\API;

use App\Contracts\Repositories\{
    Quote\QuoteSubmittedRepositoryInterface as QuoteSubmittedRepository,
    Customer\CustomerRepositoryInterface as CustomerRepository
};
use App\Http\Controllers\Controller;
use App\Http\Requests\S4\StoreContractRequest;

class S4QuoteController extends Controller
{
    protected $quote;

    public function __construct(QuoteSubmittedRepository $quote, CustomerRepository $customer)
    {
        $this->quote = $quote;
        $this->customer = $customer;
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

    /**
     * Retrieve if exists Price List file by RFQ number.
     *
     * @param string $rfq
     * @return \Illuminate\Http\Response
     */
    public function price(string $rfq)
    {
        return response()->download(
            $this->quote->price($rfq)
        );
    }

    /**
     * Retrieve if exists Schedule file by RFQ number.
     *
     * @param string $rfq
     * @return \Illuminate\Http\Response
     */
    public function schedule(string $rfq)
    {
        return response()->download(
            $this->quote->schedule($rfq)
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
        return response()->download(
            $this->quote->pdf($rfq)
        );
    }

    /**
     * Receive Quote Data from S4 and Create a Customer with New RFQ number.
     *
     * @param StoreContractRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreContractRequest $request)
    {
        return response()->json(
            $this->customer->create($request)
        );
    }
}
