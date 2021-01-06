<?php

use App\Models\Template\ContractTemplate;
use Illuminate\Database\Seeder;
use App\Models\{
    Company,
    Vendor,
    Data\Currency,
    Data\Country
};
use Illuminate\Database\Eloquent\Collection;

class ContractTemplatesSeeder extends Seeder
{
    /** @var string */
    protected string $design;

    /** @var \App\Models\Data\Currency */
    protected Currency $currency;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $templates = json_decode(file_get_contents(__DIR__ . '/models/contract_templates.json'), true);
        $this->design = file_get_contents(database_path('seeds/models/contract_template_design.json'));
        $this->currency = Currency::whereCode(Setting::get('base_currency'))->first();

        \DB::transaction(
            fn () => collect($templates)->each(
                fn ($attributes) => $this->createCompanyTemplates($attributes)
            )
        );
    }

    protected function createCompanyTemplates(array $attributes): void
    {
        $company = Company::whereVat($attributes['company']['vat'])->first();
        $vendors = Vendor::whereIn('short_code', $attributes['vendors'])->get();
        $countries = Country::whereIn('iso_3166_2', $attributes['countries'])->get();

        $vendors->each(fn ($vendor) => $this->createVendorTemplates($company, $vendor, $countries, $attributes));
    }

    protected function createVendorTemplates(Company $company, Vendor $vendor, Collection $countries, array $attributes): void
    {
        $designAttributes = $vendor->getLogoDimensionsAttribute(true) + $company->getLogoDimensionsAttribute(true);
        $formData = $this->parseDesign($this->design, $designAttributes);

        $name = implode('-', [$attributes['company']['acronym'], $vendor->short_code, $attributes['language']]);

        $template = ContractTemplate::create([
            'name'          => $name,
            'is_system'     => true,
            'form_data'     => $formData,
            'company_id'    => $company->id,
            'vendor_id'     => $vendor->id,
            'currency_id'   => $this->currency->id
        ]);

        $template->countries()->sync($countries);
    }

    protected function parseDesign(string $design, array $attributes): array
    {
        $design = preg_replace_callback('/{{(.*)}}/m', fn ($item) => data_get($attributes, last($item)), $design);

        return json_decode($design, true);
    }
}
