<?php

namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Customer\CustomerRepositoryInterface as CustomerRepository;
use App\Models\Customer\Customer;

class CustomerController extends Controller
{
    /** @var \App\Contracts\Repositories\Customer\CustomerRepositoryInterface */
    protected $customer;

    public function __construct(CustomerRepository $customer)
    {
        $this->customer = $customer;

        $this->authorizeResource(Customer::class, 'customer');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(
            $this->customer->toCollection(
                $this->customer->list()
            )
        );
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
            $this->customer->find($customer->id)
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
            $this->customer->delete($customer)
        );
    }
}
