<?php

namespace App\Console\Commands;

use App\Models\{
    Vendor,
    Company,
    Data\Country,
    Data\Currency,
    Template\QuoteTemplate,
    Template\TemplateField
};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Facades\Setting, Illuminate\Support\Str;

class TemplatesUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:templates-update';

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

        activity()->disableLogging();

        DB::transaction(function () {
            $templates = json_decode(file_get_contents(database_path('seeds/models/quote_templates.json')), true);
            $design = file_get_contents(database_path('seeds/models/template_designs.json'));
            $currency = Currency::whereCode(Setting::get('base_currency'))->first();

            foreach ($templates as $templateData) {

                foreach ($templateData['companies'] as $companyData) {
                    $company = Company::whereVat($companyData['vat'])->firstOrFail();
                    $company->acronym = $companyData['acronym'];

                    foreach ($templateData['vendors'] as $vendorCode) {
                        $vendor = Vendor::whereShortCode($vendorCode)->firstOrFail();

                        $name = "{$company->acronym}-{$vendor->short_code}-{$templateData['new_name']}";
                        $countries = Country::whereIn('iso_3166_2', $templateData['countries'])->pluck('id')->toArray();

                        $designData = array_merge($vendor->logoSelectionWithKeys, $company->logoSelectionWithKeys);
                        $templateSchema = $this->parseDesign($design, $designData);

                        $template = QuoteTemplate::firstOrNew([
                            'name' => $name,
                            'company_id' => $company->getKey(),
                            'vendor_id' => $vendor->getKey(),
                            'is_system' => true,
                        ]);

                        if ($template->exists) {
                            $template->timestamps = false;
                        }

                        $template->forceFill([
                            'name' => $name,
                            'company_id' => $company->getKey(),
                            'vendor_id' => $vendor->getKey(),
                            'currency_id' => $currency->getKey(),
                            'is_system' => true,
                            'form_data' => $templateSchema['form_data'],
                            'form_values_data' => $templateSchema['form_values_data'],
                        ])->save();

                        $template->countries()->sync($countries);

                        $this->output->write('.');
                    }
                }
            }
        });

        activity()->enableLogging();

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
