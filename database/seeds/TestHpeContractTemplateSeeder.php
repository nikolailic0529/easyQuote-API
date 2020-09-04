<?php

use App\Contracts\Repositories\CurrencyRepositoryInterface;
use App\Models\QuoteTemplate\HpeContractTemplate;
use Illuminate\Database\Seeder;
use App\Models\{Company, Vendor, Data\Country,};
use App\Services\ThumbnailManager;

class TestHpeContractTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $templates = json_decode(file_get_contents(database_path('seeds/models/test_hpe_contract_template_designs.json')), true);

        DB::beginTransaction();

        try {
            foreach ($templates as $template) {
                $this->createTemplate($template);
            }
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        DB::commit();
    }

    protected function createTemplate(array $template): void
    {
        $company = Company::query()->system()->whereShortCode('THG')->first();
        $vendor = Vendor::query()->whereShortCode('HPE')->first();
        $countries = Country::query()->whereIn('iso_3166_2', ['DE', 'CH'])->pluck('id', 'iso_3166_2');

        $images = ThumbnailManager::retrieveLogoFromModels([$company, $vendor], ThumbnailManager::WITH_KEYS);

        $formData = $this->parseDesign(json_encode($template), $images);

        /** @var CurrencyRepositoryInterface */
        $currencies = app(CurrencyRepositoryInterface::class);

        foreach ($countries as $key => $country) {
            $currency = $currencies->findByCountryCode($key);

            $name = sprintf('T%s-HPE-%s', $key, $key);

            $template = HpeContractTemplate::make([
                'name'        => $name,
                'form_data'   => $formData,
                'company_id'  => $company->getKey(),
                'vendor_id'   => $vendor->getKey(),
                'currency_id' =>  optional($currency)->getKey(),
            ]);

            $template->save();

            $template->countries()->sync($countries);
        }
    }

    protected function parseDesign(string $design, array $attributes): array
    {
        $design = preg_replace_callback('/{{(.*)}}/m', fn ($item) => data_get($attributes, last($item)), $design);

        return json_decode($design, true);
    }
}
