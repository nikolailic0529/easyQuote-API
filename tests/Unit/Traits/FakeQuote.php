<?php

namespace Tests\Unit\Traits;

use App\Models\{
    User,
    Quote\Quote
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;

trait FakeQuote
{
    protected function fakeQuote(User $user): Quote
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

        return $user->quotes()->create(
            [
                'company_id' => DB::table('companies')->value('id'),
                'vendor_id' => DB::table('vendors')->value('id'),
                'quote_template_id' => DB::table('quote_templates')->whereIsSystem(true)->value('id'),
                'customer_id' => $customer_id,
                'company_id' => DB::table('companies')->value('id'),
                'vendor_id' => DB::table('companies')->value('id'),
                'country_id' => $country_id,
                'type' => ['New', 'Renewal'][rand(0, 1)],
                'completeness' => 100
            ]
        );
    }
}
