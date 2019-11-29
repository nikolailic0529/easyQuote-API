<?php

namespace App\Console\Commands;

use App\Models\{
    Company,
    Vendor
};
use Illuminate\Console\Command;
use Arr;

class CompaniesUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:companies-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Companies Vendors';

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
        $this->info("Updating System Defined Companies...");

        activity()->disableLogging();

        \DB::transaction(function () {
            $companies = json_decode(file_get_contents(database_path('seeds/models/companies.json')), true);

            collect($companies)->each(function ($companyData) {
                $company = Company::whereVat($companyData['vat'])->first();
                $default_vendor_id = Vendor::whereShortCode($companyData['default_vendor'])->firstOrFail()->id;

                $company->update(
                    array_merge(Arr::only($companyData, ['type', 'email', 'phone', 'website']), compact('default_vendor_id'))
                );
                $vendors = Vendor::whereIn('short_code', $companyData['vendors'])->get();
                $company->vendors()->sync($vendors);
                $company->createLogo($companyData['logo'], true);

                $this->output->write('.');
            });
        });

        activity()->enableLogging();

        $this->info("\nSystem Defined Companies were updated!");
    }
}
