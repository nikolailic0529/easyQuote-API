<?php

namespace App\Listeners;

use App\Events\RfqReceived;

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
        report_logger(['message' => S4_CS_01], $event->customer);

        slack_client()
            ->title('Receiving RFQ / Data from S4')
            ->url(ui_route('customers.listing'))
            ->status([S4_CSS_01, 'Proposed RFQ' => $event->customer['rfq_number']])
            ->image(assetExternal(SN_IMG_S4RDS))
            ->send();
    }
}
