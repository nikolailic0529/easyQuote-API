<?php

namespace Database\Seeders;

use App\Facades\Setting;
use App\Models\{Company, Data\Country, Data\Currency, Vendor};
use App\Models\Template\ContractTemplate;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class ContractTemplatesSeeder extends Seeder
{
    /** @var string */
    protected string $design;

    /** @var Currency */
    protected Currency $currency;

    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        $templates = json_decode(file_get_contents(__DIR__.'/models/contract_templates.json'), true);
        $this->design = file_get_contents(database_path('seeders/models/contract_template_design.json'));
        $this->currency = Currency::query()->whereCode(Setting::get('base_currency'))->first();

        DB::transaction(
            fn() => collect($templates)->each(
                fn($attributes) => $this->createCompanyTemplates($attributes)
            )
        );
    }

    protected function createCompanyTemplates(array $attributes): void
    {
        $company = Company::query()->whereVat($attributes['company']['vat'])->first();
        $vendors = Vendor::query()->whereIn('short_code', $attributes['vendors'])->get();
        $countries = Country::query()->whereIn('iso_3166_2', $attributes['countries'])->get();

        $vendors->each(fn($vendor) => $this->createVendorTemplates($company, $vendor, $countries, $attributes));
    }

    protected function createVendorTemplates(Company $company, Vendor $vendor, Collection $countries, array $attributes): void
    {
        $designAttributes = $vendor->getLogoDimensionsAttribute() + $company->getLogoDimensionsAttribute();
        $formData = $this->parseDesign($this->design, $designAttributes);

        $name = implode('-', [$attributes['company']['acronym'], $vendor->short_code, $attributes['language']]);

        $template = ContractTemplate::create([
            'name' => $name,
            'business_division_id' => BD_RESCUE,
            'contract_type_id' => CT_CONTRACT,
            'is_system' => true,
            'form_data' => $formData,
            'company_id' => $company->getKey(),
            'vendor_id' => $vendor->getKey(),
            'currency_id' => $this->currency->getKey()
        ]);

        $template->countries()->sync($countries);
    }

    protected function parseDesign(string $design, array $attributes): array
    {
        $design = preg_replace_callback('/{{(.*)}}/m', fn($item) => data_get($attributes, last($item)), $design);

        return json_decode($design, true);
    }
}
