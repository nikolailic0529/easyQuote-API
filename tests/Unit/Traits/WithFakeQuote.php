<?php

namespace Tests\Unit\Traits;

use App\Models\{
    User,
    Quote\Quote
};
use App\Models\Customer\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;

trait WithFakeQuote
{
    /**
     * Quote Repository.
     *
     * @var \App\Repositories\Quote\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * Quote instance.
     *
     * @var \App\Models\Quote\Quote
     */
    protected $quote;

    protected function setUpFakeQuote()
    {
        $this->quoteRepository = app('quote.state');
    }

    protected function firstOrCreateQuote()
    {
        return $this->createQuote($this->user);
    }

    protected function createQuote(User $user): Quote
    {
        return app('quote.state')->create(
            $this->makeGenericQuoteAttributes($user)
        );
    }

    protected function makeGenericQuoteAttributes(?User $user = null): array
    {
        $customer = factory(Customer::class)->create();

        return factory(Quote::class)
            ->raw(['user_id' => optional($user)->id, 'customer_id' => $customer->id]);
    }
}
