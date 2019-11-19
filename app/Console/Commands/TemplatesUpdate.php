<?php

namespace App\Console\Commands;

use App\Models\{
    Vendor,
    Company,
    Data\Country,
    QuoteTemplate\QuoteTemplate,
    QuoteTemplate\TemplateField
};
use Illuminate\Console\Command;
use Str;

class TemplatesUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'templates:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update System Defined Templates';

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
    public function handle()
    {
        $this->info("Updating System Defined Templates...");

        $templates = json_decode(file_get_contents(database_path('seeds/models/quote_templates.json')), true);
        $design = file_get_contents(database_path('seeds/models/template_designs.json'));

        $templateFields = TemplateField::system()->pluck('id')->toArray();

        collect($templates)->each(function ($template) use ($templateFields, $design) {

            collect($template['companies'])->each(function ($companyData) use ($template, $templateFields, $design) {
                $company = Company::whereVat($companyData['vat'])->first();
                $company->acronym = $companyData['acronym'];

                collect($template['vendors'])->each(function ($vendorCode) use ($company, $template, $templateFields, $design) {
                    $vendor = Vendor::whereShortCode($vendorCode)->first();
                    $vendor_id = $vendor->id;
                    $company_id = $company->id;
                    $is_system = true;

                    $companyShortCode = Str::short($company->name);
                    $name = "{$company->acronym}-{$vendor->short_code}-{$template['new_name']}";
                    $countries = Country::whereIn('iso_3166_2', $template['countries'])->pluck('id')->toArray();

                    $designData = array_merge($vendor->getLogoDimensionsAttribute(true), $company->getLogoDimensionsAttribute(true));
                    $design = $this->parseDesign($design, $designData);

                    $template = QuoteTemplate::updateOrCreate(
                        compact('name', 'company_id', 'vendor_id', 'is_system'),
                        array_merge(compact('name', 'company_id', 'vendor_id', 'is_system'), $design)
                    );

                    $template->templateFields()->syncWithoutDetaching($templateFields);
                    $template->countries()->sync($countries);

                    $this->output->write('.');
                });
            });
        });

        $this->info("\nSystem Defined Templates were updated!");
    }

    protected function parseDesign(string $design, array $data)
    {
        $design = preg_replace_callback('/{{(.*)}}/m', function ($item) use ($data) {
            return $data[last($item)] ?? null;
        }, $design);

        return json_decode($design, true);
    }
}
