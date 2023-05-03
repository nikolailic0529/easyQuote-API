<?php

namespace App\Domain\Worldwide\Controllers\V1\Quote;

use App\Domain\Shared\Ownership\Services\ModelOwnershipService;
use App\Domain\Worldwide\DataTransferObjects\Quote\ChangeQuoteOwnershipData;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Requests\Quote\ChangeQuoteOwnershipRequest;
use App\Foundation\Http\Controller;
use Illuminate\Http\Response;

class WorldwideQuoteOwnershipController extends Controller
{
    /**
     * Change worldwide quote ownership.
     */
    public function changeQuoteOwnership(
        ChangeQuoteOwnershipRequest $request,
        ModelOwnershipService $service,
        ChangeQuoteOwnershipData $data,
        WorldwideQuote $worldwideQuote,
    ): Response {
        $this->authorize('changeOwnership', $worldwideQuote);

        $service->setCauser($request->user())
            ->changeOwnership(
                model: $worldwideQuote,
                data: $data->toChangeOwnershipData()
            );

        if ($data->version_ownership) {
            $service->changeOwnership(
                model: $request->mustGetQuoteVersion(),
                data: $data->toChangeOwnershipData()
            );
        }

        return response()->noContent();
    }
}
