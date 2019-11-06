<?php

use Illuminate\Database\Seeder;
use App\Models \ {
    Company,
    Vendor,
    Data\Country,
    QuoteTemplate\TemplateField
};

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

        $design = json_decode(file_get_contents(database_path('seeds/models/template_designs.json')), true);
        $form_data = json_encode($design['form_data']);
        $form_values_data = json_encode($design['form_values_data']);
        $design = compact('form_data', 'form_values_data');

        $templateFields = collect(TemplateField::select('id')->get()->each->setAppends([])->toArray())->filter()->flatten();

        collect($templates)->each(function ($template) use ($templateFields, $design) {

            collect($template['companies'])->each(function ($companyData) use ($template, $templateFields, $design) {
                $company = Company::whereVat($companyData['vat'])->first();
                $company->acronym = $companyData['acronym'];

                collect($template['vendors'])->each(function ($vendorCode) use ($company, $template, $templateFields, $design) {
                    $vendor = Vendor::whereShortCode($vendorCode)->first();
                    $vendor_id = $vendor->id;
                    $company_id = $company->id;
                    $id = $quote_template_id = (string) Uuid::generate(4);
                    $is_system = true;
                    $created_at = $updated_at = $activated_at = now()->toDateTimeString();

                    $templateName = $template['new_name'];
                    $name = "{$company->acronym}-{$vendor->short_code}-{$templateName}";

                    DB::table('quote_templates')->insert(
                        array_merge(
                            compact('id', 'name', 'is_system', 'company_id', 'vendor_id', 'created_at', 'updated_at', 'activated_at'),
                            $design
                        )
                    );

                    $templateFields->each(function ($template_field_id) use ($quote_template_id) {
                        DB::table('quote_template_template_field')->insert(
                            compact('quote_template_id', 'template_field_id')
                        );
                    });

                    collect($template['countries'])->each(function ($countryIso) use ($quote_template_id) {
                        $country_id = Country::where('iso_3166_2', $countryIso)->first()->id;
                        DB::table('country_quote_template')->insert(compact('quote_template_id', 'country_id'));
                    });
                });
            });
        });
    }
}
