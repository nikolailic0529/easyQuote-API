<?php

namespace App\Listeners;

use App\Events\RfqReceived;
use App\Facades\CustomerFlow;
use App\Models\Customer\Customer;
use App\Http\Resources\CustomerResponseResource;

class RfqReceivedListener
{
    /**
     * Handle the event.
     *
     * @param  RfqReceived  $event
     * @return void
     */
    public function handle(RfqReceived $event)
    {
        report_logger(['message' => S4_CS_01], CustomerResponseResource::make($event->customer));

        CustomerFlow::migrateCustomer($event->customer);

        slack()
            ->title('Receiving RFQ / Data from S4')
            ->url(ui_route('customers.listing'))
            ->status([S4_CSS_01, 'Proposed RFQ' => $event->customer->rfq])
            ->image(assetExternal(SN_IMG_S4RDS))
            ->queue();

        activity()
            ->on($event->customer)
            ->withProperties(['attributes' => Customer::logChanges($event->customer)])
            ->causedByService($event->service)
            ->queue('created');
    }
}
