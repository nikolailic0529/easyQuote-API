<?php

namespace App\Http\Controllers\API\Contracts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Contracts\Services\ContractState;
use App\Contracts\Services\QuoteServiceInterface as QuoteService;
use App\Http\Requests\Quote\StoreContractStateRequest;
use App\Models\Quote\Contract;
use App\Http\Resources\ContractVersionResource;
use App\Http\Resources\QuoteReviewResource;

class ContractStateController extends Controller
{
    protected ContractState $processor;

    public function __construct(ContractState $processor)
    {
        $this->processor = $processor;

        $this->authorizeResource(Contract::class, 'contract');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * @param  \App\Models\Quote\Contract $contract
     * @return \Illuminate\Http\Response
     */
    public function show(Contract $contract)
    {
        return response()->json(
            ContractVersionResource::make($contract)
        );
    }

    /**
     * Display the specified resource prepared for review.
     *
     * @param  \App\Models\Quote\Contract $contract
     * @param  \App\Contracts\Services\QuoteServiceInterface $service
     * @return \Illuminate\Http\Response
     */
    public function review(Contract $contract, QuoteService $service)
    {
        $this->authorize('view', $contract);

        $service->prepareQuoteReview($contract->usingVersion);

        return response()->json(
            QuoteReviewResource::make($contract->usingVersion->enableReview())
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Quote\StoreContractStateRequest  $request
     * @param  \App\Models\Quote\Contract $contract
     * @return \Illuminate\Http\Response
     */
    public function update(StoreContractStateRequest $request, Contract $contract)
    {
        $this->authorize('state', $contract);

        $resource = $this->processor->storeState($request->validated(), $contract);

        return response()->json(
            ContractVersionResource::make($resource)
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
