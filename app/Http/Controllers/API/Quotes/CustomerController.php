<?php

namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\{
    Services\QuoteState,
    Services\CustomerState,
};
use App\Http\Requests\Customer\CreateEqCustomer;
use App\Models\{
    InternalCompany,
    Customer\Customer,
};
use App\Services\EqCustomerService;
use App\Facades\CustomerFlow;
use App\Http\Requests\Customer\UpdateEqCustomer;
use App\Http\Resources\CustomerRepositoryResource;
use App\Services\CustomerQueries;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Customer::class, 'customer', [
            'except' => ['store', 'update']
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  CustomerQueries $queries
     * @return \Illuminate\Http\Response
     */
    public function index(CustomerQueries $queries)
    {
        return response()->json(
            CustomerRepositoryResource::collection($queries->listingQuery()->get())
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  CreateEqCustomer  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateEqCustomer $request, CustomerState $customerState, QuoteState $quoteState)
    {
        $customer = tap($customerState->createFromEqData($request->getEQCustomerData()), function (Customer $customer) {
            CustomerFlow::migrateCustomer($customer);
        }); 

        $quote = $quoteState->create(EqCustomerService::retrieveQuoteAttributes($customer));

        return response()->json($quote->load('customer:id,name,rfq'), Response::HTTP_CREATED);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateEqCustomer  $request
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateEqCustomer $request, CustomerState $customerState)
    {
        $this->authorize('update', $customer = $request->getCustomer());

        $resource = tap($customerState->updateFromEqData($customer, $request->getEQCustomerData()), function (Customer $customer) {
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
    public function destroy(Customer $customer, CustomerState $processor)
    {
        $processor->deleteCustomer($customer);

        return response()->json([true], Response::HTTP_OK);
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
