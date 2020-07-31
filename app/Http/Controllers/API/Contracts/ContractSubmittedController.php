<?php

namespace App\Http\Controllers\API\Contracts;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Contract\ContractSubmittedRepositoryInterface as Contracts;
use App\Http\Resources\Contract\SubmittedCollection;
use App\Models\Quote\Contract;

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
     * @return \Illuminate\Http\Response
     */
    public function index()
    {       
        $resource = request()->filled('search')
            ? $this->contracts->search(request('search'))
            : $this->contracts->paginate();

        return response()->json(
            SubmittedCollection::make($resource)
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
