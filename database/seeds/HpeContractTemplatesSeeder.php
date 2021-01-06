<?php

use App\Contracts\Repositories\CurrencyRepositoryInterface;
use App\Models\Template\HpeContractTemplate;
use Illuminate\Database\Seeder;
use App\Models\{Company, Vendor, Data\Country,};
use App\Services\ThumbnailManager;
use Illuminate\Support\Facades\DB;

class HpeContractTemplatesSeeder extends Seeder
{
    /** @var string */
    protected string $design;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::beginTransaction();

        $templates = json_decode(file_get_contents(__DIR__ . '/models/hpe_contract_templates.json'), true);
        $hpeTemplateSchema = file_get_contents(database_path('seeds/models/hpe_contract_template_design.json'));
        $arubaTemplateSchema = file_get_contents(database_path('seeds/models/aruba_contract_template_design.json'));

        foreach ($templates as $template) {
            if ($template['vendor'] === 'ARU') {
                $templateSchema = $arubaTemplateSchema;
            } else {
                $templateSchema = $hpeTemplateSchema;
            }

            $this->createTemplate($template, $templateSchema);
        }

        DB::commit();
    }

    protected function createTemplate(array $attributes, $templateSchema): void
    {
        $company = Company::query()->system()->whereShortCode($attributes['company'])->first();
        $vendor = Vendor::query()->whereShortCode($attributes['vendor'])->first();
        $countries = Country::query()->whereIn('iso_3166_2', $attributes['countries'])->pluck('id');

        $template = HpeContractTemplate::query()->withoutGlobalScopes()->firstOrNew(['id' => $attributes['id']]);

        $images = ThumbnailManager::retrieveLogoFromModels([$company, $vendor], ThumbnailManager::WITH_KEYS);

        $formData = $this->parseTemplateSchema($templateSchema, $images);

        /** @var CurrencyRepositoryInterface */
        $currencies = app(CurrencyRepositoryInterface::class);

        $currency = $currencies->findByCountryCode(head($attributes['countries']));

        $template->forceFill([
            'id'            => $attributes['id'],
            'name'          => $attributes['name'],
            'is_system'     => true,
            'form_data'     => $formData,
            'company_id'    => $company->getKey(),
            'vendor_id'     => $vendor->getKey(),
            'currency_id'   => optional($currency)->getKey(),
            'deleted_at'    => null,
        ])->save();

        $template->countries()->sync($countries);
    }

    protected function parseTemplateSchema(string $design, array $attributes): array
    {
        $design = preg_replace_callback('/{{(.*)}}/m', fn ($item) => data_get($attributes, last($item)), $design);

        return json_decode($design, true);
    }
}
