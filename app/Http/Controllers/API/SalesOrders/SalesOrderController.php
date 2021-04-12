<?php

namespace App\Http\Controllers\API\SalesOrders;

use App\Contracts\Services\ProcessesSalesOrderState;
use App\DTO\SalesOrder\Submit\SubmitSalesOrderResult;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Responsable;
use App\Http\Requests\{SalesOrder\CancelSalesOrder, SalesOrder\DraftSalesOrder, SalesOrder\UpdateSalesOrder};
use App\Http\Resources\SalesOrder\SalesOrderState;
use App\Models\SalesOrder;
use App\Queries\CancelSalesOrderReasonQueries;
use App\Services\SalesOrder\SalesOrderDataMapper;
use App\Services\WorldwideQuoteDataMapper;
use App\Services\WorldwideQuoteExporter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SalesOrderController extends Controller
{
    protected ProcessesSalesOrderState $orderProcessor;

    public function __construct(ProcessesSalesOrderState $orderProcessor)
    {
        $this->orderProcessor = $orderProcessor;
    }

    /**
     * Show available Cancel Sales Order Reasons.
     *
     * @param CancelSalesOrderReasonQueries $queries
     * @return JsonResponse
     */
    public function showCancelSalesOrderReasonsList(CancelSalesOrderReasonQueries $queries): JsonResponse
    {
        return response()->json(
            $queries->listingQuery()->get()
        );
    }

    /**
     * Draft a newly created Sales Order.
     *
     * @param DraftSalesOrder $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function draftSalesOrder(DraftSalesOrder $request): JsonResponse
    {
        $this->authorize('create', SalesOrder::class);

        $result = $this->orderProcessor->draftSalesOrder($request->getDraftSalesOrderData());

        return response()->json($result, Response::HTTP_CREATED);
    }

    /**
     * Show the existing Sales Order State.
     *
     * @param SalesOrder $salesOrder
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showSalesOrderState(SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('view', $salesOrder);

        return response()->json(
            SalesOrderState::make($salesOrder)
        );
    }

    /**
     * Show mapped preview data of the Sales Order.
     *
     * @param SalesOrder $salesOrder
     * @param SalesOrderDataMapper $salesOrderDataMapper
     * @param WorldwideQuoteDataMapper $quoteDataMapper
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showSalesOrderPreviewData(SalesOrder $salesOrder,
                                              SalesOrderDataMapper $salesOrderDataMapper,
                                              WorldwideQuoteDataMapper $quoteDataMapper): JsonResponse
    {
        $this->authorize('view', $salesOrder);

        if (is_null($salesOrder->worldwideQuote)) {
            return response()->json([
                "message" => "The Quote of Sales Order was not found."
            ], Response::HTTP_NOT_FOUND);
        }

        $quotePreviewData = $quoteDataMapper->mapWorldwideQuotePreviewData($salesOrder->worldwideQuote);

        return response()->json(
            $salesOrderDataMapper->mapWorldwideQuotePreviewDataAsSalesOrderPreviewData($salesOrder, $quotePreviewData),
            Response::HTTP_OK
        );
    }

    /**
     * Update the existing drafted Sales Order.
     *
     * @param UpdateSalesOrder $request
     * @param SalesOrder $salesOrder
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateSalesOrder(UpdateSalesOrder $request, SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('update', $salesOrder);

        $result = $this->orderProcessor->updateSalesOrder($request->getUpdateSalesOrderData(), $salesOrder);

        return response()->json($result, Response::HTTP_OK);
    }

    /**
     * Submit the existing drafted Sales Order.
     *
     * @param SalesOrder $salesOrder
     * @return SubmitSalesOrderResult
     * @throws AuthorizationException
     */
    public function submitSalesOrder(SalesOrder $salesOrder): SubmitSalesOrderResult
    {
        $this->authorize('update', $salesOrder);

        return $this->orderProcessor->submitSalesOrder($salesOrder);
    }

    /**
     * Unravel the existing Sales Order.
     *
     * @param SalesOrder $salesOrder
     * @return Response
     * @throws AuthorizationException
     */
    public function unravelSalesOrder(SalesOrder $salesOrder): Response
    {
        $this->authorize('update', $salesOrder);

        $this->orderProcessor->unravelSalesOrder($salesOrder);

        return response()->noContent();
    }

    /**
     * Mark as active the existing Sales Order.
     *
     * @param SalesOrder $salesOrder
     * @return Response
     * @throws AuthorizationException
     */
    public function activateSalesOrder(SalesOrder $salesOrder): Response
    {
        $this->authorize('update', $salesOrder);

        $this->orderProcessor->activateSalesOrder($salesOrder);

        return response()->noContent();
    }

    /**
     * Mark as inactive the existing Sales Order.
     *
     * @param SalesOrder $salesOrder
     * @return Response
     * @throws AuthorizationException
     */
    public function deactivateSalesOrder(SalesOrder $salesOrder): Response
    {
        $this->authorize('update', $salesOrder);

        $this->orderProcessor->deactivateSalesOrder($salesOrder);

        return response()->noContent();
    }

    /**
     * Cancel the existing Sales Order.
     *
     * @param CancelSalesOrder $request
     * @param SalesOrder $salesOrder
     * @return Responsable
     * @throws AuthorizationException
     */
    public function cancelSalesOrder(CancelSalesOrder $request, SalesOrder $salesOrder): Responsable
    {
        $this->authorize('cancel', $salesOrder);

        return $this->orderProcessor->cancelSalesOrder($request->getCancelSalesOrderData(), $salesOrder);
    }

    /**
     * Delete the existing Sales Order.
     *
     * @param SalesOrder $salesOrder
     * @return Response
     * @throws AuthorizationException
     */
    public function deleteSalesOrder(SalesOrder $salesOrder): Response
    {
        $this->authorize('delete', $salesOrder);

        $this->orderProcessor->deleteSalesOrder($salesOrder);

        return response()->noContent();
    }

    /**
     * Export the submitted Sales Order.
     *
     * @param SalesOrder $salesOrder
     * @param SalesOrderDataMapper $salesOrderDataMapper
     * @param WorldwideQuoteDataMapper $quoteDataMapper
     * @param WorldwideQuoteExporter $exporter
     * @return Response
     * @throws AuthorizationException
     */
    public function exportSalesOrder(SalesOrder $salesOrder,
                                     SalesOrderDataMapper $salesOrderDataMapper,
                                     WorldwideQuoteDataMapper $quoteDataMapper,
                                     WorldwideQuoteExporter $exporter): Response
    {
        $this->authorize('view', $salesOrder);

        $quoteExportData = $quoteDataMapper->mapWorldwideQuotePreviewDataForExport($salesOrder->worldwideQuote);
        $orderExportData = $salesOrderDataMapper->mapWorldwideQuotePreviewDataAsSalesOrderPreviewData($salesOrder, $quoteExportData);

        return $exporter->export($orderExportData);
    }
}
