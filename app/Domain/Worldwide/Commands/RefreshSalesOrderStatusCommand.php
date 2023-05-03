<?php

namespace App\Domain\Worldwide\Commands;

use App\Domain\Worldwide\Models\SalesOrder;
use App\Domain\Worldwide\Services\SalesOrder\RefreshSalesOrderStatusService;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;

class RefreshSalesOrderStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:refresh-order-status {number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the sales order status';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(RefreshSalesOrderStatusService $service, LogManager $logManager): int
    {
        $orderNumber = $this->argument('number');

        /** @var SalesOrder|null $salesOrder */
        $salesOrder = SalesOrder::query()->where('order_number', $orderNumber)->first();

        if (is_null($salesOrder)) {
            $this->error("Sales Order number `$orderNumber` has not been found.");

            return self::INVALID;
        }

        $service
            ->setLogger($logManager->stack(['stdout', 'sales-orders']))
            ->refreshStatusOf($salesOrder);

        return self::SUCCESS;
    }
}
