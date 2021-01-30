<?php

use Illuminate\Database\Seeder;
use App\Models \ {
    Company,
    Vendor,
    Data\Country,
    Template\TemplateField
};
use App\Models\Data\Currency;
use App\Facades\Setting;

class QuoteTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Empty the quote_templates, country_quote_template (pivot), quote_template_template_field (pivot) tables
        Schema::disableForeignKeyConstraints();

        DB::table('quote_templates')->delete();
        DB::table('country_quote_template')->delete();
        DB::table('quote_template_template_field')->delete();

        Schema::enableForeignKeyConstraints();

        $templates = json_decode(file_get_contents(__DIR__ . '/models/quote_templates.json'), true);
        $design = file_get_contents(database_path('seeds/models/template_designs.json'));
        $currency_id = Currency::whereCode(Setting::get('base_currency'))->firstOrFail()->id;

        $templateFields = TemplateField::system()->pluck('id')->toArray();

        collect($templates)->each(function ($template) use ($templateFields, $design, $currency_id) {

            collect($template['companies'])->each(function ($companyData) use ($template, $templateFields, $design, $currency_id) {
                $company = Company::whereVat($companyData['vat'])->first();
                $company->acronym = $companyData['acronym'];

                collect($template['vendors'])->each(function ($vendorCode) use ($company, $template, $templateFields, $design, $currency_id) {
                    $vendor = Vendor::whereShortCode($vendorCode)->first();
                    $vendor_id = $vendor->id;
                    $company_id = $company->id;
                    $id = $quote_template_id = (string) Uuid::generate(4);
                    $is_system = true;
                    $created_at = $updated_at = $activated_at = now()->toDateTimeString();

                    $templateName = $template['new_name'];
                    $name = "{$company->acronym}-{$vendor->short_code}-{$templateName}";

                    $designData = array_merge($vendor->getLogoDimensionsAttribute(true), $company->getLogoDimensionsAttribute(true));

                    $design = $this->parseDesign($design, $designData);

                    DB::table('quote_templates')->insert(
                        array_merge(
                            compact('id', 'name', 'is_system', 'company_id', 'vendor_id', 'currency_id', 'created_at', 'updated_at', 'activated_at'),
                            $design
                        )
                    );

                    $templateFields = array_map(function ($template_field_id) use ($quote_template_id) {
                        return compact('quote_template_id', 'template_field_id');
                    }, $templateFields);

                    DB::table('quote_template_template_field')->insert($templateFields);

                    collect($template['countries'])->each(function ($countryIso) use ($quote_template_id) {
                        $country_id = Country::where('iso_3166_2', $countryIso)->first()->id;
                        DB::table('country_quote_template')->insert(compact('quote_template_id', 'country_id'));
                    });
                });
            });
        });
    }

    protected function parseDesign(string $design, array $data)
    {
        $design = preg_replace_callback('/{{(.*)}}/m', function ($item) use ($data) {
            return $data[last($item)] ?? null;
        }, $design);

        $design = json_decode($design, true);
        $form_data = json_encode($design['form_data']);
        $form_values_data = json_encode($design['form_values_data']);
        return compact('form_data', 'form_values_data');
    }
}
