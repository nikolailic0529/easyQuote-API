<?php

namespace App\Domain\SalesUnit\Controllers\V1;

use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\SalesUnit\Queries\SalesUnitQueries;
use App\Domain\SalesUnit\Requests\BulkCreateOrUpdateSalesUnitsRequest;
use App\Domain\SalesUnit\Resources\V1\SalesUnitCollection;
use App\Domain\SalesUnit\Services\SalesUnitEntityService;
use App\Foundation\Http\Controller;
use Illuminate\Http\Request;

class SalesUnitController extends Controller
{
    /**
     * List Sales Units.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showListOfSalesUnits(Request $request, SalesUnitQueries $queries): SalesUnitCollection
    {
        $this->authorize('viewAny', SalesUnit::class);

        return SalesUnitCollection::make($queries->listSalesUnitsQuery($request)->get());
    }

    /**
     * Bulk create or update Sales Units.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function bulkCreateOrUpdateSalesUnits(BulkCreateOrUpdateSalesUnitsRequest $request,
                                                 SalesUnitEntityService $service): SalesUnitCollection
    {
        $this->authorize('create', SalesUnit::class);

        $collection = $service->bulkCreateOrUpdateSalesUnits($request->getCreateOrUpdateSalesUnitDataCollection());

        return SalesUnitCollection::make($collection);
    }
}
