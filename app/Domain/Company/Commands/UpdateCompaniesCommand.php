<?php

namespace App\Domain\Company\Commands;

use App\Domain\Company\Models\Company;
use App\Domain\Country\Models\Country;
use App\Domain\Image\Services\ThumbHelper;
use App\Domain\Vendor\Models\Vendor;
use Illuminate\Console\Command;

class UpdateCompaniesCommand extends Command
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
    protected $description = 'Update system defined Company entities';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->output->title('Updating the system defined Companies...');

        activity()->disableLogging();

        $companies = yaml_parse_file(database_path('seeders/models/companies.yaml'));

        $this->withProgressBar($companies, function (array $data) {
            $this->performCompanyUpdate($data);
        });

        $this->newLine();

        activity()->enableLogging();

        return self::SUCCESS;
    }

    protected function performCompanyUpdate(array $data): void
    {
        /** @var Company $company */
        $company = Company::query()->where('vat', $data['vat'])->sole();

        tap($company, function (Company $company) use ($data) {
            $defaultVendorID = Vendor::query()->where('short_code', $data['default_vendor'])->value((new Vendor())->getQualifiedKeyName());
            $defaultCountryID = Country::query()->where('iso_3166_2', $data['default_country'])->value((new Country())->getQualifiedKeyName());

            $company->type = $data['type'];
            $company->email = $data['email'];
            $company->phone = $data['phone'];
            $company->website = $data['website'];
            $company->defaultVendor()->associate($defaultVendorID);
            $company->defaultCountry()->associate($defaultCountryID);

            $company->save();

            $vendors = Vendor::query()->whereIn('short_code', $data['vendors'])->pluck((new Vendor())->getQualifiedKeyName())->all();

            $company->vendors()->sync($vendors);

            $company->createLogo($data['logo'], true);

            if (isset($data['svg_logo'])) {
                ThumbHelper::updateModelSvgThumbnails($company, base_path($data['svg_logo']));
            }
        });
    }
}
