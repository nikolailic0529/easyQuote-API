<?php

namespace App\Domain\Worldwide\Controllers\V1\SalesOrder;

use App\Domain\Worldwide\Contracts\ProcessesSalesOrderState;
use App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit\SubmitSalesOrderResult;
use App\Domain\Worldwide\Models\SalesOrder;
use App\Domain\Worldwide\Queries\CancelSalesOrderReasonQueries;
use App\Domain\Worldwide\Requests\SalesOrder\CancelSalesOrderRequest;
use App\Domain\Worldwide\Requests\SalesOrder\DraftSalesOrderRequest;
use App\Domain\Worldwide\Requests\SalesOrder\UpdateSalesOrderRequest;
use App\Domain\Worldwide\Resources\V1\SalesOrder\SalesOrderState;
use App\Domain\Worldwide\Services\SalesOrder\RefreshSalesOrderStatusService;
use App\Domain\Worldwide\Services\SalesOrder\SalesOrderDataMapper;
use App\Domain\Worldwide\Services\WorldwideQuote\Models\QuoteExportResult;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteDataMapper;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteExporter;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Responsable;
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
     * @throws AuthorizationException
     */
    public function draftSalesOrder(DraftSalesOrderRequest $request): JsonResponse
    {
        $this->authorize('create', SalesOrder::class);

        $result = $this->orderProcessor->draftSalesOrder($request->getDraftSalesOrderData());

        return response()->json($result, Response::HTTP_CREATED);
    }

    /**
     * Show the existing Sales Order State.
     *
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
     * @throws AuthorizationException
     */
    public function showSalesOrderPreviewData(SalesOrder $salesOrder,
                                              SalesOrderDataMapper $salesOrderDataMapper,
                                              WorldwideQuoteDataMapper $quoteDataMapper): JsonResponse
    {
        $this->authorize('view', $salesOrder);

        if (is_null($salesOrder->worldwideQuote)) {
            return response()->json([
                'message' => 'The Quote of Sales Order was not found.',
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
     * @throws AuthorizationException
     */
    public function updateSalesOrder(UpdateSalesOrderRequest $request, SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('update', $salesOrder);

        $result = $this->orderProcessor->updateSalesOrder($request->getUpdateSalesOrderData(), $salesOrder);

        return response()->json($result, Response::HTTP_OK);
    }

    /**
     * Submit the existing drafted Sales Order.
     *
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
     * @throws AuthorizationException
     */
    public function cancelSalesOrder(CancelSalesOrderRequest $request, SalesOrder $salesOrder): Responsable
    {
        $this->authorize('cancel', $salesOrder);

        return $this->orderProcessor->cancelSalesOrder($request->getCancelSalesOrderData(), $salesOrder);
    }

    /**
     * Refresh status of the existing Sales Order from the API.
     *
     * @throws AuthorizationException
     */
    public function refreshSalesOrderStatus(RefreshSalesOrderStatusService $service, SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('view', $salesOrder);

        $service->refreshStatusOf($salesOrder);

        return response()->json(
            SalesOrderState::make($salesOrder)
        );
    }

    /**
     * Delete the existing Sales Order.
     *
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
     * @throws AuthorizationException
     */
    public function exportSalesOrder(SalesOrder $salesOrder,
                                     SalesOrderDataMapper $salesOrderDataMapper,
                                     WorldwideQuoteDataMapper $quoteDataMapper,
                                     WorldwideQuoteExporter $exporter): QuoteExportResult
    {
        $this->authorize('view', $salesOrder);

        $quoteExportData = $quoteDataMapper->mapWorldwideQuotePreviewDataForExport($salesOrder->worldwideQuote);
        $orderExportData = $salesOrderDataMapper->mapWorldwideQuotePreviewDataAsSalesOrderPreviewData($salesOrder, $quoteExportData);

        return $exporter->export($orderExportData, $salesOrder);
    }
}
