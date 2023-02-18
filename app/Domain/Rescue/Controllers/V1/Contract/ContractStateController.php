<?php

namespace App\Domain\Rescue\Controllers\V1\Contract;

use App\Domain\Rescue\Contracts\ContractState;
use App\Domain\Rescue\Contracts\ContractView;
use App\Domain\Rescue\Models\Contract;
use App\Domain\Rescue\Requests\StoreContractStateRequest;
use App\Domain\Rescue\Resources\V1\ContractReview;
use App\Domain\Rescue\Resources\V1\ContractVersionResource;
use App\Foundation\Http\Controller;
use Illuminate\Http\Request;

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
     * @param  \App\Domain\Rescue\Requests\ShowContractStateRequest
     *
     * @return \Illuminate\Http\Response
     */
    public function show(\App\Domain\Rescue\Requests\ShowContractStateRequest $request, Contract $contract)
    {
        return response()->json(
            ContractVersionResource::make(
                $request->loadContractAttributes($contract)
            )
        );
    }

    /**
     * Display the specified resource prepared for review.
     *
     * @return \Illuminate\Http\Response
     */
    public function review(Contract $contract, ContractView $service)
    {
        $this->authorize('view', $contract);

        $service->prepareContractReview($contract);

        return response()->json(
            ContractReview::make($contract->enableReview())
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(StoreContractStateRequest $request, Contract $contract)
    {
        $this->authorize('state', $contract);

        $resource = $this->processor->storeState($request->validated(), $contract);

        return response()->json(
            ContractVersionResource::make(
                $request->loadContractAttributes($resource)
            )
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    }
}
