<?php

namespace App\Http\Controllers\API\V1\SalesUnit;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesUnit\BulkCreateOrUpdateSalesUnits;
use App\Http\Resources\V1\SalesUnit\SalesUnitCollection;
use App\Models\SalesUnit;
use App\Queries\SalesUnitQueries;
use App\Services\SalesUnit\SalesUnitEntityService;
use Illuminate\Http\Request;

class SalesUnitController extends Controller
{
    /**
     * List Sales Units.
     *
     * @param Request $request
     * @param SalesUnitQueries $queries
     * @return SalesUnitCollection
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
     * @param BulkCreateOrUpdateSalesUnits $request
     * @param SalesUnitEntityService $service
     * @return SalesUnitCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function bulkCreateOrUpdateSalesUnits(BulkCreateOrUpdateSalesUnits $request,
                                                 SalesUnitEntityService       $service): SalesUnitCollection
    {
        $this->authorize('create', SalesUnit::class);

        $collection = $service->bulkCreateOrUpdateSalesUnits($request->getCreateOrUpdateSalesUnitDataCollection());

        return SalesUnitCollection::make($collection);
    }
}
