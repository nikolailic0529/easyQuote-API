<?php

use App\Models\QuoteTemplate\ContractTemplate;
use Illuminate\Database\Seeder;
use App\Models\{
    Company,
    Vendor,
    Data\Currency,
    Data\Country
};

class ContractTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $templates = json_decode(file_get_contents(__DIR__ . '/models/contract_templates.json'), true);
        $design = file_get_contents(database_path('seeds/models/contract_template_design.json'));

        \DB::transaction(function () use ($templates, $design) {
            collect($templates)->each(function ($attributes) use ($design) {
                $company = Company::whereVat($attributes['company']['vat'])->first();
                $vendor = Vendor::whereShortCode($attributes['vendor'])->first();
                $currency = Currency::whereCode(Setting::get('base_currency'))->first();
                $countries = Country::whereIn('iso_3166_2', $attributes['countries'])->get();

                $designAttributes = $vendor->getLogoDimensionsAttribute(true) + $company->getLogoDimensionsAttribute(true);
                $formData = $this->parseDesign($design, $designAttributes);

                $name = implode('-', [$attributes['company']['acronym'], $vendor->short_code, $attributes['language']]);

                $template = tap(ContractTemplate::make()->forceFill([
                    'name'          => $name,
                    'is_system'     => true,
                    'form_data'     => $formData,
                    'company_id'    => $company->id,
                    'vendor_id'     => $vendor->id,
                    'currency_id'   => $currency->id,

                ]))->save();

                $template->countries()->sync($countries);
            });
        });
    }

    protected function parseDesign(string $design, array $attributes): array
    {
        $design = preg_replace_callback('/{{(.*)}}/m', function ($item) use ($attributes) {
            return $attributes[last($item)] ?? null;
        }, $design);

        return json_decode($design, true);
    }
}
