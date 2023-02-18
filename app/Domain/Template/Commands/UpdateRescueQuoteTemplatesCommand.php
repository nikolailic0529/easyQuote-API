<?php

namespace App\Domain\Template\Commands;

use App\Domain\Company\Models\Company;
use App\Domain\Country\Models\Country;
use App\Domain\Currency\Models\Currency;
use App\Domain\Image\Services\ThumbHelper;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\Vendor\Models\Vendor;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class UpdateRescueQuoteTemplatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:update-rescue-quote-templates';

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
        $this->output->title('Updating System Defined Templates...');

        $templates = json_decode(file_get_contents(database_path('seeders/models/quote_templates.json')), true);

        $design = file_get_contents(database_path('seeders/models/template_designs.json'));

        $currency = Currency::query()->where('code', 'GBP')->firstOrFail();

        activity()->disableLogging();

        $this->output->progressStart();

        foreach ($templates as $templateData) {
            foreach ($templateData['companies'] as $companyData) {
                /** @var \App\Domain\Company\Models\Company $company */
                $company = Company::query()->where('vat', $companyData['vat'])->firstOrFail();

                $company->acronym = $companyData['acronym'];

                foreach ($templateData['vendors'] as $vendorCode) {
                    /** @var \App\Domain\Vendor\Models\Vendor $vendor */
                    $vendor = Vendor::query()->where('short_code', $vendorCode)->firstOrFail();

                    $name = "{$company->acronym}-{$vendor->short_code}-{$templateData['new_name']}";

                    $countries = Country::query()->whereIn('iso_3166_2', $templateData['countries'])->pluck('id')->all();

                    $templateImages = array_merge(
                        ThumbHelper::getLogoDimensionsFromImage(
                            $vendor->image,
                            $vendor->thumbnailProperties(),
                            Str::snake(class_basename($vendor))
                        ),
                        ThumbHelper::getLogoDimensionsFromImage(
                            $vendor->image,
                            $vendor->thumbnailProperties(),
                            Str::snake(class_basename($vendor)).'_1'
                        ),
                        ThumbHelper::getLogoDimensionsFromImage(
                            $company->image,
                            $company->thumbnailProperties(),
                            Str::snake(class_basename($company))
                        )
                    );

                    $templateSchema = $this->parseDesign($design, $templateImages);

                    /** @var \App\Domain\Rescue\Models\QuoteTemplate $template */
                    $template = QuoteTemplate::query()->firstOrNew([
                        'name' => $name,
                        'company_id' => $company->getKey(),
                        'vendor_id' => $vendor->getKey(),
                        'is_system' => true,
                    ]);

                    with($template, function (QuoteTemplate $quoteTemplate) use ($name, $company, $vendor, $currency, $templateSchema, $countries) {
                        if ($quoteTemplate->exists) {
                            $quoteTemplate->timestamps = false;
                        }

                        $quoteTemplate->name = $name;
                        $quoteTemplate->company_id = $company->getKey();
                        $quoteTemplate->vendor_id = $vendor->getKey();
                        $quoteTemplate->currency_id = $currency->getKey();

                        $quoteTemplate->business_division_id = BD_RESCUE;
                        $quoteTemplate->contract_type_id = CT_CONTRACT;

                        $quoteTemplate->is_system = true;
                        $quoteTemplate->form_data = $templateSchema['form_data'];
                        $quoteTemplate->form_values_data = $templateSchema['form_values_data'];

                        $quoteTemplate->saveOrFail();

                        $quoteTemplate->vendors()->sync($vendor);
                        $quoteTemplate->countries()->sync($countries);
                    });

                    $this->output->progressAdvance();
                }
            }
        }

        $this->output->progressFinish();

        activity()->enableLogging();

        $this->output->success('System Defined Quote Templates were updated!');
    }

    protected function parseDesign(string $design, array $data)
    {
        $design = preg_replace_callback('/{{(.*)}}/m', function ($item) use ($data) {
            return $data[last($item)] ?? null;
        }, $design);

        return json_decode($design, true);
    }
}
