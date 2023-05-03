<?php

namespace App\Domain\Rescue\Controllers\V1\Contract;

use App\Domain\Rescue\Contracts\ContractSubmittedRepositoryInterface as Contracts;
use App\Domain\Rescue\Models\Contract;
use App\Domain\UnifiedContract\Queries\UnifiedContractQueries;
use App\Domain\UnifiedContract\Requests\PaginateContractsRequest;
use App\Domain\UnifiedContract\Resources\V1\SubmittedCollection;
use App\Domain\UnifiedContract\Services\UnifiedContractDataMapper;
use App\Foundation\Http\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

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
     */
    public function index(PaginateContractsRequest $request,
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
