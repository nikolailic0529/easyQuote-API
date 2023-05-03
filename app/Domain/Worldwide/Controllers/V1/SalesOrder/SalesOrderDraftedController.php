<?php

namespace App\Domain\Worldwide\Controllers\V1\SalesOrder;

use App\Domain\Worldwide\Models\SalesOrder;
use App\Domain\Worldwide\Queries\SalesOrderQueries;
use App\Domain\Worldwide\Requests\SalesOrder\PaginateSalesOrdersRequest;
use App\Domain\Worldwide\Resources\V1\SalesOrder\SalesOrderDraft;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SalesOrderDraftedController extends Controller
{
    /**
     * Display a listing of the drafted Sales Orders.
     *
     * @throws AuthorizationException
     */
    public function __invoke(PaginateSalesOrdersRequest $request, SalesOrderQueries $queries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SalesOrder::class);

        $paginator = $queries->paginateDraftedOrdersQuery($request)->apiPaginate();

        return SalesOrderDraft::collection($paginator);
    }
}
