<?php

namespace App\Domain\Rescue\Controllers\V1;

use App\Domain\Company\Models\{InternalCompany};
use App\Domain\Rescue\Contracts\CustomerState;
use App\Domain\Rescue\Contracts\QuoteState;
use App\Domain\Rescue\Facades\CustomerFlow;
use App\Domain\Rescue\Models\Customer;
use App\Domain\Rescue\Queries\CustomerQueries;
use App\Domain\Rescue\Requests\CreateEqCustomerRequest;
use App\Domain\Rescue\Requests\UpdateEqCustomerRequest;
use App\Domain\Rescue\Resources\V1\CustomerRepositoryResource;
use App\Domain\Rescue\Services\EqCustomerService;
use App\Foundation\Http\Controller;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Customer::class, 'customer', [
            'except' => ['store', 'update'],
        ]);
    }

    /**
     * Display a listing of the resource.
     *
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
     * @return \Illuminate\Http\Response
     */
    public function store(CreateEqCustomerRequest $request, CustomerState $customerState, QuoteState $quoteState)
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
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateEqCustomerRequest $request, CustomerState $customerState)
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
     * @param \App\Domain\Rescue\Models\Customer $company
     *
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
     * @param \App\Domain\Company\Models\InternalCompany $company
     *
     * @return \Illuminate\Http\Response
     */
    public function giveCustomerNumber(InternalCompany $company, EqCustomerService $service, ?Customer $customer = null)
    {
        return response()->json([
            'rfq_number' => $service->giveNumber($company, $customer),
        ]);
    }
}
