<?php

namespace App\Console\Commands;

use App\Contracts\Repositories\{
    CountryRepositoryInterface as Countries,
    VendorRepositoryInterface as Vendors,
    CompanyRepositoryInterface as Companies
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

    /** @var \App\Contracts\Repositories\CompanyRepositoryInterface */
    protected Companies $companies;

    /** @var \App\Contracts\Repositories\VendorRepositoryInterface */
    protected Vendors $vendors;

    /** @var \App\Contracts\Repositories\CountryRepositoryInterface */
    protected Countries $countries;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Companies $companies, Vendors $vendors, Countries $countries)
    {
        parent::__construct();

        $this->companies = $companies;
        $this->vendors = $vendors;
        $this->countries = $countries;
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
                $company = $this->companies->findByVat($companyData['vat']);

                if (is_null($company)) {
                    $this->line('E');
                    return true;
                }

                $default_vendor_id = optional($this->vendors->findByCode($companyData['default_vendor']))->id;
                $default_country_id = app('country.repository')->findIdByCode($companyData['default_country']);

                $company->update(
                    array_merge(Arr::only($companyData, ['type', 'email', 'phone', 'website']), compact('default_vendor_id', 'default_country_id'))
                );

                $vendors = $this->vendors->findByCode($companyData['vendors']);
                $company->vendors()->sync($vendors);

                $company->createLogo($companyData['logo'], true);

                $this->output->write('.');
            });
        });

        activity()->enableLogging();

        $this->info("\nSystem Defined Companies were updated!");
    }
}
