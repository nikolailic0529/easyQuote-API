<?php

namespace Tests\Unit\Quote;

use App\Domain\Rescue\Commands\NotifyQuotesExpirationCommand;
use App\Domain\Rescue\Models\Customer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * @group build
 */
class QuoteServiceTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test quote expiry notification.
     *
     * @return void
     */
    public function testQuoteExpiryNotification()
    {
        $customer = factory(Customer::class)->state('expired')->create();
        $quote = factory(\App\Domain\Rescue\Models\Quote::class)->create(['customer_id' => $customer->id]);

        Artisan::call(NotifyQuotesExpirationCommand::class);

        $notificationKey = 'expired';

        $this->assertEquals(1, $quote->notifications()->where(['notification_key' => $notificationKey])->count());

        /* Assert that expiry notification for one quote is sent only once. */
        Artisan::call(NotifyQuotesExpirationCommand::class);

        $this->assertEquals(1, $quote->notifications()->where(['notification_key' => $notificationKey])->count());
    }
}
