<?php

namespace App\Domain\Worldwide\Controllers\V1\Quote;

use App\Domain\Worldwide\Models\WorldwideCustomer;
use App\Domain\Worldwide\Queries\WorldwideCustomerQueries;
use App\Foundation\Http\Controller;
use Illuminate\Http\Request;

class WorldwideCustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request
     *
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
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(WorldwideCustomer $worldwideCustomer)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, WorldwideCustomer $worldwideCustomer)
    {
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(WorldwideCustomer $worldwideCustomer)
    {
    }
}
