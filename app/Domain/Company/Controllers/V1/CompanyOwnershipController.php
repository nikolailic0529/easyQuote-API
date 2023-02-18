<?php

namespace App\Domain\Company\Controllers\V1;

use App\Domain\Company\DataTransferObjects\ChangeCompanyOwnershipData;
use App\Domain\Company\Models\Company;
use App\Domain\Shared\Ownership\Services\ModelOwnershipService;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CompanyOwnershipController extends Controller
{
    /**
     * Change company ownership.
     *
     * @throws AuthorizationException
     */
    public function changeCompanyOwnership(
        Request $request,
        ModelOwnershipService $service,
        ChangeCompanyOwnershipData $data,
        Company $company
    ): Response {
        $this->authorize('changeOwnership', $company);

        $service
            ->setCauser($request->user())
            ->changeOwnership($company, $data->toChangeOwnershipData());

        return response()->noContent();
    }
}
