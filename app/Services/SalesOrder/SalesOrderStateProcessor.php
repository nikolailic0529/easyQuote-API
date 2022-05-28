<?php


namespace App\Services\SalesOrder;


use App\Contracts\Services\ProcessesSalesOrderState;
use App\DTO\SalesOrder\Cancel\CancelSalesOrderData;
use App\DTO\SalesOrder\Cancel\CancelSalesOrderResult;
use App\DTO\SalesOrder\DraftSalesOrderData;
use App\DTO\SalesOrder\Submit\SubmitSalesOrderResult;
use App\DTO\SalesOrder\UpdateSalesOrderData;
use App\Enum\Lock;
use App\Enum\SalesOrderStatus;
use App\Enum\VAT;
use App\Events\SalesOrder\SalesOrderCancelled;
use App\Events\SalesOrder\SalesOrderDeleted;
use App\Events\SalesOrder\SalesOrderDrafted;
use App\Events\SalesOrder\SalesOrderSubmitted;
use App\Events\SalesOrder\SalesOrderUnravel;
use App\Events\SalesOrder\SalesOrderUpdated;
use App\Helpers\PipelineShortCodeResolver;
use App\Helpers\SpaceShortCodeResolver;
use App\Models\Quote\WorldwideQuote;
use App\Models\SalesOrder;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Carbon;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SalesOrderStateProcessor implements ProcessesSalesOrderState
{
    const SO_NUM_FMT = "{space}-WW-{pipeline}-{contract_type}SO%'.07d";

    protected ConnectionInterface $connection;
    protected LockProvider $lockProvider;
    protected ValidatorInterface $validator;
    protected EventDispatcher $eventDispatcher;
    protected SubmitSalesOrderService $submitService;
    protected CancelSalesOrderService $cancelService;
    protected SalesOrderDataMapper $dataMapper;

    public function __construct(ConnectionInterface $connection,
                                LockProvider $lockProvider,
                                EventDispatcher $eventDispatcher,
                                ValidatorInterface $validator,
                                SubmitSalesOrderService $submitService,
                                CancelSalesOrderService $cancelService,
                                SalesOrderDataMapper $dataMapper)
    {
        $this->connection = $connection;
        $this->lockProvider = $lockProvider;
        $this->validator = $validator;
        $this->eventDispatcher = $eventDispatcher;
        $this->submitService = $submitService;
        $this->cancelService = $cancelService;
        $this->dataMapper = $dataMapper;
    }

    /**
     * @inheritDoc
     */
    public function draftSalesOrder(DraftSalesOrderData $data): SalesOrder
    {
        return tap(new SalesOrder(), function (SalesOrder $salesOrder) use ($data) {
            $salesOrder->user()->associate($data->user_id);
            $salesOrder->worldwideQuote()->associate($data->worldwide_quote_id);
            $salesOrder->salesOrderTemplate()->associate($data->sales_order_template_id);

            if ($data->vat_type === VAT::VAT_NUMBER) {
                $salesOrder->vat_number = $data->vat_number;
            } else {
                $salesOrder->vat_number = null;
            }

            $salesOrder->vat_type = $data->vat_type;
            $salesOrder->customer_po = $data->customer_po;
            $salesOrder->contract_number = $data->contract_number;
            $salesOrder->activated_at = $salesOrder->freshTimestampString();

            $this->assignNewSalesOrderNumber($salesOrder);

            $salesOrder->assets_count = SalesOrderAssetsCountResolver::of($salesOrder)();

            $this->connection->transaction(fn() => $salesOrder->save());

            // TODO: handle "so-created" event and index a new order

            $this->eventDispatcher->dispatch(
                new SalesOrderDrafted($salesOrder)
            );
        });
    }

    protected function assignNewSalesOrderNumber(SalesOrder $salesOrder): void
    {
        $quote = $salesOrder->worldwideQuote;
        $pipeline = $quote->opportunity->pipeline;
        $contractTypeName = $quote->contractType->type_short_name;
        $space = $pipeline->space;

        $pipelineShortCode = (new PipelineShortCodeResolver())($pipeline->pipeline_name);
        $spaceShortCode = (new SpaceShortCodeResolver())($space->space_name);

        $numberFormat = strtr(self::SO_NUM_FMT, [
            '{space}' => $spaceShortCode,
            '{pipeline}' => $pipelineShortCode,
            '{contract_type}' => strtoupper(substr($contractTypeName, 0, 1))
        ]);

        $salesOrder->order_number = sprintf($numberFormat, $quote->sequence_number);
    }

    /**
     * @inheritDoc
     */
    public function updateSalesOrder(UpdateSalesOrderData $data, SalesOrder $salesOrder): SalesOrder
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationFailedException($data, $violations);
        }

        return tap($salesOrder, function (SalesOrder $salesOrder) use ($data) {
            $salesOrder->salesOrderTemplate()->associate($data->sales_order_template_id);

            if ($data->vat_type === VAT::VAT_NUMBER) {
                $salesOrder->vat_number = $data->vat_number;
            } else {
                $salesOrder->vat_number = null;
            }

            $salesOrder->vat_type = $data->vat_type;
            $salesOrder->customer_po = $data->customer_po;
            $salesOrder->contract_number = $data->contract_number;

            $lock = $this->lockProvider->lock(Lock::UPDATE_SORDER($salesOrder->getKey()), 10);

            $lock->block(30, function () use ($salesOrder) {

                $this->connection->transaction(fn() => $salesOrder->save());

            });

            // TODO: handle "so-updated" event

            $this->eventDispatcher->dispatch(
                new SalesOrderUpdated($salesOrder)
            );
        });
    }

    /**
     * @inheritDoc
     */
    public function submitSalesOrder(SalesOrder $salesOrder): SubmitSalesOrderResult
    {
        $submitOrderData = $this->dataMapper->mapSalesOrderToSubmitSalesOrderData($salesOrder);

        $submitResult = $this->submitService->processSalesOrderDataSubmission($submitOrderData);

        $salesOrder->order_date = $submitOrderData->order_date;
        $salesOrder->submitted_at = Carbon::now();
        $salesOrder->status = $submitResult->status;
        $salesOrder->failure_reason = $submitResult->status_reason;
        $salesOrder->exchange_rate = $submitOrderData->exchange_rate;

        $lock = $this->lockProvider->lock(Lock::UPDATE_SORDER($salesOrder->getKey()), 10);

        $lock->block(30, function () use ($salesOrder) {

            $this->connection->transaction(fn() => $salesOrder->save());

        });

        // TODO: handle "so-submitted" event

        $this->eventDispatcher->dispatch(
            new SalesOrderSubmitted($salesOrder)
        );

        return $submitResult;
    }

    public function cancelSalesOrder(CancelSalesOrderData $data, SalesOrder $salesOrder): CancelSalesOrderResult
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationFailedException($data, $violations);
        }

        $cancelResult = $this->cancelService->processSalesOrderCancellation($data);

        if (false === $cancelResult->response_ok) {
            return $cancelResult;
        }

        $salesOrder->status = SalesOrderStatus::CANCEL;
        $salesOrder->status_reason = $data->status_reason;

        $lock = $this->lockProvider->lock(Lock::UPDATE_SORDER($salesOrder->getKey()), 10);

        $lock->block(30, function () use ($salesOrder) {

            $this->connection->transaction(fn() => $salesOrder->save());

        });

        $this->eventDispatcher->dispatch(
            new SalesOrderCancelled($salesOrder)
        );

        return $cancelResult;
    }

    /**
     * @inheritDoc
     */
    public function unravelSalesOrder(SalesOrder $salesOrder): void
    {
        $salesOrder->submitted_at = null;

        $lock = $this->lockProvider->lock(Lock::UPDATE_SORDER($salesOrder->getKey()), 10);

        $lock->block(30, function () use ($salesOrder) {

            $this->connection->transaction(fn() => $salesOrder->save());

        });

        // TODO: handle "so-unravel" event

        $this->eventDispatcher->dispatch(
            new SalesOrderUnravel($salesOrder)
        );
    }

    /**
     * @inheritDoc
     */
    public function activateSalesOrder(SalesOrder $salesOrder): void
    {
        $lock = $this->lockProvider->lock(Lock::UPDATE_SORDER($salesOrder->getKey()), 10);

        $salesOrder->activated_at = $salesOrder->freshTimestampString();

        $lock->block(30, function () use ($salesOrder) {

            $this->connection->transaction(fn() => $salesOrder->save());

        });
    }

    /**
     * @inheritDoc
     */
    public function deactivateSalesOrder(SalesOrder $salesOrder): void
    {
        $lock = $this->lockProvider->lock(Lock::UPDATE_SORDER($salesOrder->getKey()), 10);

        $salesOrder->activated_at = null;

        $lock->block(30, function () use ($salesOrder) {

            $this->connection->transaction(fn() => $salesOrder->save());

        });
    }

    /**
     * @inheritDoc
     */
    public function deleteSalesOrder(SalesOrder $salesOrder): void
    {
        $lock = $this->lockProvider->lock(Lock::UPDATE_SORDER($salesOrder->getKey()), 10);

        $lock->block(30, function () use ($salesOrder) {

            $this->connection->transaction(fn() => $salesOrder->delete());

        });

        // TODO: handle "so-deleted" event

        $this->eventDispatcher->dispatch(
            new SalesOrderDeleted($salesOrder)
        );
    }


}
