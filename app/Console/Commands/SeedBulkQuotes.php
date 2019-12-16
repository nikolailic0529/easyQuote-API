<?php

namespace App\Console\Commands;
use App\Models\{
    User,
    Company,
    Vendor,
    QuoteTemplate\QuoteTemplate
};
use Illuminate\Support\{
    Collection,
    Str,
    Facades\DB
};
use Webpatser\Uuid\Uuid;

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

        $bar->finish();

        $this->line(PHP_EOL . '<comment>Total Quotes seeded:</comment> ' . $count);
    }

    protected function prepareData(): Collection
    {
        $countries = collect(['US', 'GB', 'CA'])->transform(function ($code) {
            return app('country.repository')->findIdByCode($code);
        });

        $users = User::pluck('id');
        $companies = Company::pluck('id');
        $vendors = Vendor::pluck('id');
        $templates = QuoteTemplate::system()->pluck('id');

        return collect(compact('countries', 'users', 'companies', 'vendors', 'templates'));
    }

    protected function performInsert(Collection $data)
    {
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
    }
}
