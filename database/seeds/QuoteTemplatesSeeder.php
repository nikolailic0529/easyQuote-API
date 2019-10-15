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

        $templateFields = collect(TemplateField::select('id')->get()->each->setAppends([])->toArray())->filter()->flatten();

        collect($templates)->each(function ($template) use ($templateFields) {

            collect($template['companies'])->each(function ($vat) use ($template, $templateFields) {
                $company = Company::whereVat($vat)->first();

                collect($template['vendors'])->each(function ($vendorCode) use ($company, $template, $templateFields) {
                    $vendor = Vendor::whereShortCode($vendorCode)->first();
                    $vendor_id = $vendor->id;
                    $company_id = $company->id;
                    $id = $quote_template_id = (string) Uuid::generate(4);
                    $is_system = true;
                    $created_at = $updated_at = $activated_at = now()->toDateTimeString();

                    $templateName = $template['name'];
                    $companyShortCode = Str::short($company->name);
                    $name = "{$companyShortCode} {$vendor->short_code} $templateName";

                    DB::table('quote_templates')->insert(
                        compact('id', 'name', 'is_system', 'company_id', 'vendor_id', 'created_at', 'updated_at', 'activated_at')
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
