<?php

namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\{
    Customer\CustomerRepositoryInterface as Customers,
};
use App\Http\Requests\Customer\CreateEqCustomer;
use App\Http\Resources\Customer\EqCustomer as EqCustomerResource;
use App\Models\{
    InternalCompany,
    Customer\Customer,
};
use App\Services\EqCustomerService;
use App\Facades\CustomerFlow;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    protected Customers $customers;

    public function __construct(Customers $customers)
    {
        $this->customers = $customers;

        $this->authorizeResource(Customer::class, 'customer', [
            'except' => 'store'
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
    public function store(CreateEqCustomer $request)
    {
        $resource = tap(
            $this->customers->create($request->validated()),
            fn (Customer $customer) => CustomerFlow::migrateCustomer($customer)
        );

        return response()->json(EqCustomerResource::make($resource), Response::HTTP_CREATED);
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
            $this->customers->find($customer->id)
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
    public function giveCustomerNumber(InternalCompany $company, EqCustomerService $service)
    {
        return response()->json([
            'rfq_number' => $service->giveNumber($company)
        ]);
    }
}
