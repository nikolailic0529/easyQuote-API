<?php

namespace App\Console\Commands;
use App\Models\{
    User,
    Company,
    Vendor,
    QuoteTemplate\QuoteTemplate
};
use Illuminate\Support\Collection;
use Webpatser\Uuid\Uuid;
use Arr, Str, DB;

use Illuminate\Console\Command;

class SeedBulkQuotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:seed-bulk-quotes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed bulk quotes for stress testing';

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
    public function handle()
    {
        $count = 100000;

        $bar = $this->output->createProgressBar($count);
        $data = $this->prepareData();

        DB::transaction(function () use ($data, $count, $bar) {
            collect()->times($count)->each(function () use ($data, $bar) {
                $this->performInsert($data);
                $bar->advance();
            });
        });

        app('customer.repository')->forgetDraftedCache();

        $bar->finish();

        $this->line(PHP_EOL . '<comment>Total Quotes seeded:</comment> ' . $count);
    }

    protected function prepareData(): Collection
    {
        $countries = collect(['US', 'GB', 'CA'])->transform(function ($code) {
            return app('country.repository')->findIdByCode($code);
        });

        $users = User::get();
        $companies = Company::get();
        $vendors = Vendor::pluck('id');
        $templates = QuoteTemplate::system()->pluck('id');

        return collect(compact('countries', 'users', 'companies', 'vendors', 'templates'));
    }

    protected function performInsert(Collection $data)
    {
        $country_id = $data->get('countries')->random();
        $customer_id = Uuid::generate(4)->string;

        $customer = [
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
        ];

        DB::table('customers')->insert($customer);

        $quote_id = Uuid::generate(4)->string;
        $user = $data->get('users')->random();
        $company = $data->get('companies')->random();
        $cached_relations = [
            'user' => $user->only('first_name', 'last_name'),
            'company' => $company->only('name'),
            'customer' => Arr::only($customer, ['name', 'rfq', 'support_start', 'support_end', 'valid_until'])
        ];

        DB::table('quotes')->insert([
            'id' => $quote_id,
            'customer_id' => $customer_id,
            'user_id' => $user->id,
            'quote_template_id' => $data->get('templates')->random(),
            'company_id' => $company->id,
            'vendor_id' => $data->get('vendors')->random(),
            'country_id' => $country_id,
            'type' => ['New', 'Renewal'][rand(0, 1)],
            'completeness' => 100,
            'created_at' => now(),
            'updated_at' => now(),
            'submitted_at' => (bool) rand(0, 1) ? now() : null,
            'activated_at' => (bool) rand(0, 1) ? now() : null,
            'cached_relations' => json_encode($cached_relations)
        ]);
    }
}
