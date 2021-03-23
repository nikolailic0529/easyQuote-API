<?php

namespace App\Http\Controllers\API\WorldwideQuotes;

use App\Http\Controllers\Controller;
use App\Models\Customer\WorldwideCustomer;
use App\Queries\WorldwideCustomerQueries;
use Illuminate\Http\Request;

class WorldwideCustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request
     * @param WorldwideCustomerQueries $queries
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, WorldwideCustomerQueries $queries)
    {
        return response()->json(
            $queries->listingQuery($request)->apiPaginate()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Customer\WorldwideCustomer  $worldwideCustomer
     * @return \Illuminate\Http\Response
     */
    public function show(WorldwideCustomer $worldwideCustomer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer\WorldwideCustomer  $worldwideCustomer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, WorldwideCustomer $worldwideCustomer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Customer\WorldwideCustomer  $worldwideCustomer
     * @return \Illuminate\Http\Response
     */
    public function destroy(WorldwideCustomer $worldwideCustomer)
    {
        //
    }
}
