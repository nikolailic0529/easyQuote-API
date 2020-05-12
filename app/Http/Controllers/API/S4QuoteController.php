<?php

namespace App\Http\Controllers\API;

use App\Contracts\Repositories\{
    Quote\QuoteSubmittedRepositoryInterface as QuoteSubmittedRepository,
    Customer\CustomerRepositoryInterface as CustomerRepository
};
use App\Events\RfqReceived;
use App\Http\Controllers\Controller;
use App\Http\Requests\S4\StoreContractRequest;
use App\Http\Resources\CustomerResponseResource;
use App\Http\Resources\QuoteResource;
use App\Models\Customer\Customer;

class S4QuoteController extends Controller
{
    protected $quote;

    public function __construct(QuoteSubmittedRepository $quote, CustomerRepository $customer)
    {
        $this->quote = $quote;
        $this->customer = $customer;

        $this->middleware('client:s4,proteus,triton,epd');
    }

    /**
     * Handle the incoming request.
     *
     * @param  string  $rfq
     * @return \Illuminate\Http\Response
     */
    public function show(string $rfq)
    {
        $quote = $this->quote->rfq($rfq, true);

        return response()->json(
            QuoteResource::make($quote)
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
        return $this->quote->pdf($rfq);
    }

    /**
     * Receive Quote Data from S4 and Create a Customer with New RFQ number.
     *
     * @param StoreContractRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreContractRequest $request)
    {
        $resource = tap(
            $this->customer->create($request->validated()),
            fn (Customer $customer) => dispatch(
                fn () => event(new RfqReceived($customer, $request->get('client_name', 'service')))
            )->afterResponse()
        );

        return response()->json(
            CustomerResponseResource::make($resource)
        );
    }
}
