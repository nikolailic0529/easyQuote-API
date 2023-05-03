<?php

namespace App\Domain\Worldwide\Commands;

use App\Domain\Worldwide\Contracts\ProcessesSalesOrderState;
use App\Domain\Worldwide\Enum\SalesOrderStatus;
use App\Domain\Worldwide\Models\SalesOrder;
use Illuminate\Console\Command;

class SubmitSalesOrderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:submit-sales-order {order_number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Submit the specified Sales Order';

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
     *
     * @return mixed
     */
    public function handle(ProcessesSalesOrderState $salesOrderProcessor)
    {
        /** @var SalesOrder $order */
        $order = SalesOrder::query()
            ->where('order_number', $orderNumber = $this->argument('order_number'))
            ->first();

        if (is_null($order)) {
            $this->output->error("Unable to find Sales Order, Number: '$orderNumber'.");

            return Command::FAILURE;
        }

        $result = $salesOrderProcessor->submitSalesOrder($order);

        if ($result->status === SalesOrderStatus::SENT) {
            $this->output->success("Sales Order Number '$orderNumber' has been submitted successfully.");

            return Command::SUCCESS;
        }

        $this->output->error([
            "Unable to submit Order Number '$orderNumber'.",
            $result->status_reason,
        ]);

        return Command::FAILURE;
    }
}
