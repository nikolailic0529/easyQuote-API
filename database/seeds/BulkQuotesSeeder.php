<?php

use App\Models\Company;
use App\Models\QuoteTemplate\QuoteTemplate;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;

class BulkQuotesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $countries = collect(['US', 'GB', 'CA'])->transform(function ($code) {
            return app('country.repository')->findIdByCode($code);
        });
        $users = User::pluck('id');
        $companies = Company::pluck('id');
        $vendors = Vendor::pluck('id');
        $templates = QuoteTemplate::system()->pluck('id');

        $data = collect(compact('countries', 'users', 'companies', 'vendors', 'templates'));

        DB::transaction(function () use ($data) {
            collect()->times(100000)->lazy()->each(function () use ($data) {
                $customer_id = Uuid::generate(4)->string;
                $country_id = $data->get('countries')->random();

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

                $quote_id = Uuid::generate(4)->string;
                DB::table('quotes')->insert([
                    'id' => $quote_id,
                    'customer_id' => $customer_id,
                    'user_id' => $data->get('users')->random(),
                    'quote_template_id' => $data->get('templates')->random(),
                    'company_id' => $data->get('companies')->random(),
                    'vendor_id' => $data->get('vendors')->random(),
                    'country_id' => $country_id,
                    'type' => ['New', 'Renewal'][rand(0, 1)],
                    'completeness' => 100,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'submitted_at' => (bool) rand(0, 1) ? now() : null
                ]);
            });
        });
    }
}
