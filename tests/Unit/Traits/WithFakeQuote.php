<?php

namespace Tests\Unit\Traits;

use App\Models\{
    User,
    Quote\Quote
};
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
        $uses = array_flip(class_uses_recursive(static::class));
        $this->quoteRepository = app('quote.repository');

        if (isset($uses[WithFakeUser::class])) {
            $this->quote = $this->createQuote($this->user);
        }
    }

    protected function createQuote(User $user): Quote
    {
        return app('quote.repository')->create(
            $this->makeGenericQuoteAttributes($user)
        );
    }

    protected function makeGenericQuoteAttributes(?User $user = null): array
    {
        $customer_id = Uuid::generate(4)->string;
        $country_id = DB::table('countries')->value('id');

        DB::table('customers')->insert([
            'id' => $customer_id,
            'support_start' => now(),
            'support_end' => now()->addYears(2),
            'valid_until' => now()->addYears(2),
            'name' => 'Loomis UK Limited',
            'payment_terms' => 'Loomis UK Limited',
            'invoicing_terms' => 'Upfront',
            'country_id' => $country_id,
            'rfq' => Str::upper(Str::random(20)),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return [
            'company_id' => DB::table('companies')->value('id'),
            'vendor_id' => DB::table('vendors')->value('id'),
            'quote_template_id' => DB::table('quote_templates')->whereIsSystem(true)->value('id'),
            'customer_id' => $customer_id,
            'country_id' => $country_id,
            'type' => ['New', 'Renewal'][rand(0, 1)],
            'completeness' => 100,
            'user_id' => $user->id ?? null
        ];
    }
}
