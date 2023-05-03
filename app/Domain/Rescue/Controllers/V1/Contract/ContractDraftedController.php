<?php

namespace App\Domain\Rescue\Controllers\V1\Contract;

use App\Domain\Rescue\Contracts\ContractDraftedRepositoryInterface as Contracts;
use App\Domain\Rescue\Models\Contract;
use App\Domain\Rescue\Requests\SubmitContractRequest;
use App\Domain\UnifiedContract\Queries\UnifiedContractQueries;
use App\Domain\UnifiedContract\Requests\PaginateContractsRequest;
use App\Domain\UnifiedContract\Resources\V1\DraftedCollection;
use App\Domain\UnifiedContract\Services\UnifiedContractDataMapper;
use App\Foundation\Http\Controller;
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
     */
    public function index(PaginateContractsRequest $request,
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
     * @param \App\Domain\Rescue\Requests\SubmitContractRequest
     *
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
