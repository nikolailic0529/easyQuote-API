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
        //Empty the quote_templates, quote_template_template_field, company_quote_template, vendor_quote_template, country_quote_template tables
        Schema::disableForeignKeyConstraints();

        DB::table('quote_templates')->delete();
        DB::table('quote_template_template_field')->delete();
        DB::table('company_quote_template')->delete();
        DB::table('vendor_quote_template')->delete();
        DB::table('country_quote_template')->delete();

        Schema::enableForeignKeyConstraints();

        $templates = json_decode(file_get_contents(__DIR__ . '/models/quote_templates.json'), true);

        $templateFieldsIds = collect(TemplateField::select('id')->get()->each->setAppends([])->toArray())->filter()->flatten();

        collect($templates)->each(function ($template) use ($templateFieldsIds) {
            $templateId = (string) Uuid::generate(4);

            DB::table('quote_templates')->insert([
                'id' => $templateId,
                'name' => $template['name'],
                'is_system' => true
            ]);

            $templateFieldsIds->each(function ($fieldId) use ($templateId) {
                DB::table('quote_template_template_field')->insert([
                    'quote_template_id' => $templateId,
                    'template_field_id' => $fieldId
                ]);
            });

            collect($template['companies'])->each(function ($vat) use ($templateId) {
                $companyId = Company::whereVat($vat)->first()->id;

                DB::table('company_quote_template')->insert([
                    'company_id' => $companyId,
                    'quote_template_id' => $templateId
                ]);
            });

            collect($template['vendors'])->each(function ($vendorCode) use ($templateId) {
                $vendorId = Vendor::whereShortCode($vendorCode)->first()->id;

                DB::table('vendor_quote_template')->insert([
                    'vendor_id' => $vendorId,
                    'quote_template_id' => $templateId
                ]);
            });

            collect($template['countries'])->each(function ($countryIso) use ($templateId) {
                $countryId = Country::where('iso_3166_2', $countryIso)->first()->id;

                DB::table('country_quote_template')->insert([
                    'country_id' => $countryId,
                    'quote_template_id' => $templateId
                ]);
            });
        });
    }
}
