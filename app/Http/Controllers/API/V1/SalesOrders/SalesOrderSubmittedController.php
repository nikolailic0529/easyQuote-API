<?php

namespace App\Http\Controllers\API\V1\SalesOrders;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesOrder\PaginateSalesOrders;
use App\Http\Resources\V1\SalesOrder\SalesOrderSubmitted;
use App\Models\SalesOrder;
use App\Queries\SalesOrderQueries;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SalesOrderSubmittedController extends Controller
{
    /**
     * Display a listing of the drafted Sales Orders.
     *
     * @param PaginateSalesOrders $request
     * @param SalesOrderQueries $queries
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function __invoke(PaginateSalesOrders $request, SalesOrderQueries $queries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SalesOrder::class);

        $paginator = $request->transformSalesOrdersQuery($queries->paginateSubmittedOrdersQuery($request))->apiPaginate();

        return SalesOrderSubmitted::collection($paginator);
    }
}
