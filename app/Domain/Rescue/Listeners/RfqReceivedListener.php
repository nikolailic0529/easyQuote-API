<?php

namespace App\Domain\Rescue\Listeners;

use App\Domain\Rescue\Events\Customer\RfqReceived;
use App\Domain\Rescue\Facades\CustomerFlow;
use App\Domain\Rescue\Models\Customer;
use App\Domain\Rescue\Resources\V1\CustomerResponseResource;

class RfqReceivedListener
{
    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(RfqReceived $event)
    {
        customlog(['message' => S4_CS_01], CustomerResponseResource::make($event->customer));

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
