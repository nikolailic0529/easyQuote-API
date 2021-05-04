<?php

namespace App\Console\Commands;

use App\Contracts\Repositories\{
    CountryRepositoryInterface as Countries,
    VendorRepositoryInterface as Vendors,
    CompanyRepositoryInterface as Companies
};
use App\Services\ThumbHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class UpdateCompanies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:update-companies';

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
    public function handle(Companies $companyRepository, Vendors $vendorRepository, Countries $countryRepository)
    {
        $this->info("Updating System Defined Companies...");

        activity()->disableLogging();

        \DB::transaction(function () use ($companyRepository, $vendorRepository, $countryRepository) {
            $companies = json_decode(file_get_contents(database_path('seeders/models/companies.json')), true);

            collect($companies)->each(function ($companyData) use ($companyRepository, $vendorRepository, $countryRepository) {
                /** @var \App\Models\Company */
                $company = $companyRepository->findByVat($companyData['vat']);

                if (is_null($company)) {
                    $this->line('E');
                    return true;
                }

                $default_vendor_id = optional($vendorRepository->findByCode($companyData['default_vendor']))->id;
                $default_country_id = $countryRepository->findIdByCode($companyData['default_country']);

                $company->update(
                    array_merge(Arr::only($companyData, ['type', 'email', 'phone', 'website']), compact('default_vendor_id', 'default_country_id'))
                );

                $vendors = $vendorRepository->findByCode($companyData['vendors']);
                $company->vendors()->sync($vendors);

                $company->createLogo($companyData['logo'], true);

                if (isset($companyData['svg_logo'])) {
                    ThumbHelper::updateModelSvgThumbnails($company, base_path($companyData['svg_logo']));
                }

                $this->output->write('.');
            });
        });

        activity()->enableLogging();

        $this->info("\nSystem Defined Companies were updated!");
    }
}
