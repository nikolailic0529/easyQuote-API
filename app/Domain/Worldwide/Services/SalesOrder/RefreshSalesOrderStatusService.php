<?php

namespace App\Domain\Worldwide\Services\SalesOrder;

use App\Domain\Sync\Enum\Lock;
use App\Domain\VendorServices\Services\CheckSalesOrderService;
use App\Domain\VendorServices\Services\Models\CheckSalesOrderResultBcOrder;
use App\Domain\Worldwide\Enum\SalesOrderStatus;
use App\Domain\Worldwide\Models\SalesOrder;
use App\Domain\Worldwide\Services\SalesOrder\Model\InterpretedOrderStatus;
use App\Foundation\Log\Contracts\LoggerAware;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class RefreshSalesOrderStatusService implements LoggerAware
{
    protected LoggerInterface $logger;

    public function __construct(protected ConnectionInterface $connection,
                                protected LockProvider $lockProvider,
                                protected CheckSalesOrderService $checkSalesOrderService,
                                LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function refreshStatusOf(SalesOrder $salesOrder): SalesOrder
    {
        return tap($salesOrder, function (SalesOrder $salesOrder) {
            $response = $this->checkSalesOrderService->checkSalesOrder($salesOrder->getKey());

            $resultOrder = $response->bc_orders[0];

            $interpretedStatus = $this->interpretResultOrderStatus($resultOrder);

            $previousStatus = $salesOrder->status;
            $previousStatusReason = $salesOrder->failure_reason;

            $salesOrder->status = $interpretedStatus->status;

            if (false === is_null($interpretedStatus->reason)) {
                $salesOrder->failure_reason = $interpretedStatus->reason;
            }

            if ($salesOrder->isClean()) {
                $this->logger->info('Sales order data has been fetched, yet no new status.', [
                    'status' => $salesOrder->status,
                    'status_reason' => $salesOrder->failure_reason,
                ]);

                return;
            }

            $this->lockProvider->lock(Lock::UPDATE_SORDER($salesOrder->getKey()), 10)
                ->block(30, function () use ($salesOrder) {
                    $this->connection->transaction(fn () => $salesOrder->save());
                });

            $this->logger->info('Sales Order status has been refreshed.', [
                'sales_order_id' => $salesOrder->getKey(),
                'previous_status' => [
                    'status' => $previousStatus,
                    'failure_reason' => $previousStatusReason,
                ],
                'new_status' => [
                    'status' => $salesOrder->status,
                    'failure_reason' => $salesOrder->failure_reason,
                ],
            ]);
        });
    }

    protected function interpretResultOrderStatus(CheckSalesOrderResultBcOrder $order): InterpretedOrderStatus
    {
        $status = match ($order->status) {
            // 0   QUEUE
            // 1   SENT
            // 2   CONFIRMED
            // 3   INVOICE_GENERATED
            // 30  CANCELED
            // 99  ON_HOLD
            // 50  UNIDENTIFIED_FAILURE
            // 51  UNAPPROVED_FAILURE
            // 52  DATA_VALIDATION_FAILURE
            // 999 SYSTEM_ERROR
            1, 2, 3 => SalesOrderStatus::SENT,
            30 => SalesOrderStatus::CANCEL,
            default => SalesOrderStatus::FAILURE,
        };

        $reason = SalesOrderStatus::SENT !== $status ? $order->status_reason : null;

        return new InterpretedOrderStatus(status: $status, reason: $reason);
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, function () use ($logger) {
            $this->logger = $logger;
        });
    }
}
