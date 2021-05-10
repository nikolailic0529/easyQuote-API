<?php

namespace App\Http\Controllers\API\Contracts;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Contract\ContractSubmittedRepositoryInterface as Contracts;
use App\Contracts\Services\ContractState;
use App\Http\Requests\UnifiedContract\PaginateContracts;
use App\Http\Resources\Contract\SubmittedCollection;
use App\Models\Quote\Contract;
use App\Queries\UnifiedContractQueries;
use App\Services\Contract\UnifiedContractDataMapper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractSubmittedController extends Controller
{
    /** @var \App\Contracts\Repositories\Quote\ContractSubmittedRepositoryInterface */
    protected $contracts;

    public function __construct(Contracts $contracts)
    {
        $this->contracts = $contracts;
        $this->authorizeResource(Contract::class, 'submitted');
    }

    /**
     * Display a listing of the Submitted Contracts.
     *
     * @param \App\Http\Requests\UnifiedContract\PaginateContracts $request
     * @param \App\Queries\UnifiedContractQueries $queries
     * @param \App\Services\Contract\UnifiedContractDataMapper $dataMapper
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(PaginateContracts $request,
                          UnifiedContractQueries $queries,
                          UnifiedContractDataMapper $dataMapper): JsonResponse
    {
        $pagination = with($request->transformContractsQuery($queries->paginateUnifiedSubmittedContractsQuery($request))->apiPaginate(), function (LengthAwarePaginator $paginator) use ($dataMapper) {
            return $dataMapper->mapUnifiedContractPaginator($paginator);
        });

        return response()->json(
            SubmittedCollection::make($pagination)
        );
    }

    /**
     * Remove the specified Submitted Contract.
     *
     * @param  \App\Models\Quote\Contract $submitted
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contract $submitted)
    {
        return response()->json(
            $this->contracts->delete($submitted->id)
        );
    }

    /**
     * Activate the specified Submitted Contract.
     *
     * @param  \App\Models\Quote\Contract $submitted
     * @return \Illuminate\Http\Response
     */
    public function activate(Contract $submitted)
    {
        $this->authorize('update', $submitted);

        return response()->json(
            $this->contracts->activate($submitted->id)
        );
    }

    /**
     * Deactivate the specified Submitted Contract.
     *
     * @param  \App\Models\Quote\Contract $submitted
     * @return \Illuminate\Http\Response
     */
    public function deactivate(Contract $submitted)
    {
        $this->authorize('update', $submitted);

        return response()->json(
            $this->contracts->deactivate($submitted->id)
        );
    }

    /**
     * Unsubmit the specified Submitted Contract.
     *
     * @param  \App\Models\Quote\Contract $submitted
     * @return \Illuminate\Http\Response
     */
    public function unsubmit(Contract $submitted)
    {
        $this->authorize('update', $submitted);

        return response()->json(
            $this->contracts->unsubmit($submitted->id)
        );
    }
}
