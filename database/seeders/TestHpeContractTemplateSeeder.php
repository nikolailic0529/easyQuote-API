<?php

namespace Database\Seeders;

use App\Domain\Company\Models\Company;
use App\Domain\Country\Models\Country;
use App\Domain\Currency\Contracts\CurrencyRepositoryInterface;
use App\Domain\HpeContract\Models\HpeContractTemplate;
use App\Domain\Image\Services\ThumbHelper;
use App\Domain\Vendor\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestHpeContractTemplateSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        $templates = json_decode(file_get_contents(database_path('seeders/models/test_hpe_contract_template_designs.json')), true);

        DB::beginTransaction();

        try {
            foreach ($templates as $template) {
                $this->createTemplate($template);
            }
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        DB::commit();
    }

    protected function createTemplate(array $template): void
    {
        $company = Company::query()->where('flags', '&', Company::SYSTEM)->where('short_code', 'THG')->first();
        $vendor = Vendor::query()->where('short_code', 'HPE')->first();
        $countries = Country::query()->whereIn('iso_3166_2', ['DE', 'CH'])->pluck('id', 'iso_3166_2');

        $images = ThumbHelper::retrieveLogoFromModels([$company, $vendor], ThumbHelper::MAP);

        $formData = $this->parseDesign(json_encode($template), $images);

        /** @var \App\Domain\Currency\Contracts\CurrencyRepositoryInterface */
        $currencies = app(CurrencyRepositoryInterface::class);

        foreach ($countries as $key => $country) {
            $currency = $currencies->findByCountryCode($key);

            $name = sprintf('T%s-HPE-%s', $key, $key);

            $template = HpeContractTemplate::make([
                'name' => $name,
                'form_data' => $formData,
                'company_id' => $company->getKey(),
                'vendor_id' => $vendor->getKey(),
                'currency_id' => optional($currency)->getKey(),
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
