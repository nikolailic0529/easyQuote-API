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
            ->changeOwnership($opportunity, $ownershipData = $data->toChangeOwnershipData());

        if ($data->transfer_attached_quote_to_new_owner) {
            foreach ($opportunity->worldwideQuotes as $quote) {
                $service->changeOwnership($quote, $ownershipData);
            }
        }

        return response()->noContent();
    }
}
