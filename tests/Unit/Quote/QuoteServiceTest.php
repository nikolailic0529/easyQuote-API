<?php

namespace Tests\Unit\Quote;

use Tests\TestCase;
use App\Console\Commands\Routine\Notifications\QuotesExpiration;
use App\Models\{
    Quote\Quote,
    Customer\Customer,
    ModelNotification,
};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;

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
        $quote = factory(Quote::class)->create(['customer_id' => $customer->id]);

        Artisan::call(QuotesExpiration::class);

        $notificationKey = 'expired';

        $this->assertEquals(1, $quote->notifications()->where(['notification_key' => $notificationKey])->count());

        /** Assert that expiry notification for one quote is sent only once. */
        Artisan::call(QuotesExpiration::class);

        $this->assertEquals(1, $quote->notifications()->where(['notification_key' => $notificationKey])->count());
    }
}
