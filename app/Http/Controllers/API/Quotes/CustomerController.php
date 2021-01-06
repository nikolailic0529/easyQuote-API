<?php

namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\{
    Repositories\Customer\CustomerRepositoryInterface as Customers,
    Services\QuoteState
};
use App\Http\Requests\Customer\CreateEqCustomer;
use App\Models\{
    InternalCompany,
    Customer\Customer,
};
use App\Services\EqCustomerService;
use App\Facades\CustomerFlow;
use App\Http\Requests\Customer\UpdateEqCustomer;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    protected Customers $customers;

    public function __construct(Customers $customers)
    {
        $this->customers = $customers;

        $this->authorizeResource(Customer::class, 'customer', [
            'except' => ['store', 'update']
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(
            $this->customers->toCollection(
                $this->customers->list()
            )
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  CreateEqCustomer  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateEqCustomer $request, QuoteState $quoteState)
    {
        $customer = tap(
            $this->customers->create($request->validated()),
            fn (Customer $customer) => CustomerFlow::migrateCustomer($customer)
        );

        $quote = $quoteState->create(EqCustomerService::retrieveQuoteAttributes($customer));

        return response()->json($quote->load('customer:id,name,rfq'), Response::HTTP_CREATED);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateEqCustomer  $request
     * @param  \App\Models\Customer\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateEqCustomer $request, Customer $customer)
    {
        $this->authorize('update', $customer);
        
        $resource = tap($this->customers->update($customer, $request->validated()), function (Customer $customer) {
            $customer->load('addresses', 'contacts', 'vendors');
            CustomerFlow::migrateCustomer($customer);
        });

        return response()->json($resource);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Customer\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function show(Customer $customer)
    {
        return response()->json(
            $customer->load('addresses', 'contacts', 'vendors')
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Customer\Customer $company
     * @return \Illuminate\Http\Response
     */
    public function destroy(Customer $customer)
    {
        return response()->json(
            $this->customers->delete($customer)
        );
    }

    /**
     * Display a new easyQuote customer RFQ number.
     *
     * @param InternalCompany $company
     * @param EqCustomerService $service
     * @return \Illuminate\Http\Response
     */
    public function giveCustomerNumber(InternalCompany $company, EqCustomerService $service, ?Customer $customer = null)
    {
        return response()->json([
            'rfq_number' => $service->giveNumber($company, $customer)
        ]);
    }
}
