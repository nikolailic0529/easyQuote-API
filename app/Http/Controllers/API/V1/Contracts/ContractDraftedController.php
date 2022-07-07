<?php

namespace App\Http\Controllers\API\V1\Contracts;

use App\Contracts\Repositories\Contract\ContractDraftedRepositoryInterface as Contracts;
use App\Http\Controllers\Controller;
use App\Http\Requests\Quote\SubmitContractRequest;
use App\Http\Requests\UnifiedContract\PaginateContracts;
use App\Http\Resources\V1\Contract\DraftedCollection;
use App\Models\{Quote\Contract};
use App\Queries\UnifiedContractQueries;
use App\Services\Contract\UnifiedContractDataMapper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

class ContractDraftedController extends Controller
{
    protected Contracts $contracts;

    public function __construct(Contracts $contracts)
    {
        $this->contracts = $contracts;
        $this->authorizeResource(Contract::class, 'drafted');
    }

    /**
     * Display a listing of unified contract entities.
     *
     * @param PaginateContracts $request
     * @param \App\Queries\UnifiedContractQueries $queries
     * @param \App\Services\Contract\UnifiedContractDataMapper $dataMapper
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(PaginateContracts $request,
                          UnifiedContractQueries $queries,
                          UnifiedContractDataMapper $dataMapper): JsonResponse
    {
        $pagination = with($request->transformContractsQuery($queries->paginateUnifiedDraftedContractsQuery($request))->apiPaginate(), function (LengthAwarePaginator $paginator) use ($dataMapper) {
            return $dataMapper->mapUnifiedContractPaginator($paginator);
        });

        return response()->json(
            DraftedCollection::make($pagination)
        );
    }

    /**
     * Remove the specified Drafted Contract.
     *
     * @param  \App\Models\Quote\Contract $drafted
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contract $drafted)
    {
        return response()->json(
            $this->contracts->delete($drafted->id)
        );
    }

    /**
     * Activate the specified Drafted Contract.
     *
     * @param  \App\Models\Quote\Contract $drafted
     * @return \Illuminate\Http\Response
     */
    public function activate(Contract $drafted)
    {
        $this->authorize('update', $drafted);

        return response()->json(
            $this->contracts->activate($drafted->id)
        );
    }

    /**
     * Deactivate the specified Drafted Contract.
     *
     * @param  \App\Models\Quote\Contract $drafted
     * @return \Illuminate\Http\Response
     */
    public function deactivate(Contract $drafted)
    {
        $this->authorize('update', $drafted);

        return response()->json(
            $this->contracts->deactivate($drafted->id)
        );
    }

    /**
     * Submit the specified Drafted Contract.
     *
     * @param \App\Http\Requests\Quote\SubmitContractRequest
     * @param  \App\Models\Quote\Contract $drafted
     * @return \Illuminate\Http\Response
     */
    public function submit(SubmitContractRequest $request, Contract $drafted)
    {
        $this->authorize('submit', $drafted);

        return response()->json(
            $this->contracts->submit($drafted->id, $request->validated())
        );
    }
}
