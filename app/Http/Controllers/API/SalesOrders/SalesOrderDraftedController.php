<?php

namespace App\Http\Controllers\API\SalesOrders;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesOrder\SalesOrderDraft;
use App\Models\SalesOrder;
use App\Queries\SalesOrderQueries;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SalesOrderDraftedController extends Controller
{
    /**
     * Display a listing of the drafted Sales Orders.
     *
     * @param Request $request
     * @param SalesOrderQueries $queries
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function __invoke(Request $request, SalesOrderQueries $queries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SalesOrder::class);

        $paginator = $queries->paginateDraftedOrdersQuery($request)->apiPaginate();

        return SalesOrderDraft::collection($paginator);
    }
}
