<?php

namespace App\Http\Controllers\API\Contracts;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Contract\ContractDraftedRepositoryInterface as Contracts;
use App\Http\Requests\Quote\SubmitContractRequest;
use App\Http\Resources\Contract\DraftedCollection;
use App\Models\{
    Quote\Contract
};

class ContractDraftedController extends Controller
{
    protected Contracts $contracts;

    public function __construct(Contracts $contracts)
    {
        $this->contracts = $contracts;
        $this->authorizeResource(Contract::class, 'drafted');
    }

    /**
     * Display a listing of the Drafted Contracts.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $resource = request()->filled('search')
            ? $this->contracts->search(request('search'))
            : $this->contracts->paginate();

        return response()->json(
            DraftedCollection::make($resource)
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
