<?php

namespace Database\Seeders;

use App\Facades\Setting;
use App\Models\{Company, Data\Country, Template\TemplateField, Vendor};
use App\Models\Data\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuoteTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeders.
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

        $templates = json_decode(file_get_contents(__DIR__.'/models/quote_templates.json'), true);
        $design = file_get_contents(database_path('seeders/models/template_designs.json'));
        $currency = Currency::query()->where('code', Setting::get('base_currency'))->firstOrFail();

        $templateFields = TemplateField::query()->where('is_system', true)->pluck('id')->all();

        collect($templates)->each(function ($template) use ($templateFields, $design, $currency) {

            collect($template['companies'])->each(function ($companyData) use ($template, $templateFields, $design, $currency) {
                $company = Company::query()->where('vat', $companyData['vat'])->first();
                $company->acronym = $companyData['acronym'];

                collect($template['vendors'])->each(function ($vendorCode) use ($company, $template, $templateFields, $design, $currency) {
                    $vendor = Vendor::query()->where('short_code', $vendorCode)->first();
                    $quoteTemplateId = (string)\Webpatser\Uuid\Uuid::generate(4);

                    $templateName = $template['new_name'];
                    $name = "{$company->acronym}-{$vendor->short_code}-{$templateName}";

                    $designData = array_merge($vendor->getLogoDimensionsAttribute(true), $company->getLogoDimensionsAttribute(true));

                    $templateSchema = $this->parseDesign($design, $designData);

                    DB::table('quote_templates')->insert([
                        'id' => $quoteTemplateId,
                        'name' => $name,
                        'is_system' => true,
                        'business_division_id' => BD_RESCUE,
                        'contract_type_id' => CT_CONTRACT,
                        'company_id' => $company->getKey(),
                        'vendor_id' => $vendor->getKey(),
                        'currency_id' => $currency->getKey(),
                        'form_data' => $templateSchema['form_data'],
                        'form_values_data' => $templateSchema['form_values_data'],
                        'created_at' => now(),
                        'updated_at' => now(),
                        'activated_at' => now()
                    ]);

                    $templateFields = array_map(function ($templateFieldId) use ($quoteTemplateId) {
                        return [
                            'quote_template_id' => $quoteTemplateId,
                            'template_field_id' => $templateFieldId
                        ];
                    }, $templateFields);

                    DB::table('quote_template_template_field')->insert($templateFields);

                    foreach ($template['countries'] as $countryCode) {
                        $countryId = Country::query()->where('iso_3166_2', $countryCode)->value('id');

                        DB::table('country_quote_template')->insert([
                            'quote_template_id' => $quoteTemplateId,
                            'country_id' => $countryId
                        ]);
                    }
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
