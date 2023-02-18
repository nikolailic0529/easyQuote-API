<?php

namespace App\Domain\Worldwide\Controllers\V1\Opportunity;

use App\Domain\Shared\Ownership\Services\ModelOwnershipService;
use App\Domain\Worldwide\DataTransferObjects\Opportunity\ChangeOpportunityOwnershipData;
use App\Domain\Worldwide\Models\Opportunity;
use App\Foundation\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OpportunityOwnershipController extends Controller
{
    /**
     * Change opportunity ownership.
     */
    public function changeOpportunityOwnership(
        Request $request,
        ModelOwnershipService $service,
        ChangeOpportunityOwnershipData $data,
        Opportunity $opportunity
    ): Response {
        $this->authorize('changeOwnership', $opportunity);

        $service->setCauser($request->user())
            ->changeOwnership($opportunity, $data->toChangeOwnershipData());

        return response()->noContent();
    }
}
