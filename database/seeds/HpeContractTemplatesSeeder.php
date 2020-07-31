<?php

use App\Contracts\Repositories\CurrencyRepositoryInterface;
use App\Models\QuoteTemplate\HpeContractTemplate;
use Illuminate\Database\Seeder;
use App\Models\{
    Company,
    Vendor,
    Data\Currency,
    Data\Country,
    Image
};
use App\Services\CurrencyHelper;
use App\Services\ThumbnailManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

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
        $templates = json_decode(file_get_contents(__DIR__ . '/models/hpe_contract_templates.json'), true);
        $this->design = file_get_contents(database_path('seeds/models/hpe_contract_template_design.json'));

        \DB::transaction(
            fn () => collect($templates)->each(
                fn ($attributes) => $this->createTemplate($attributes)
            )
        );
    }

    protected function createTemplate(array $attributes): void
    {
        $company = Company::query()->system()->whereShortCode($attributes['company'])->first();
        $vendor = Vendor::query()->whereShortCode($attributes['vendor'])->first();
        $countries = Country::query()->whereIn('iso_3166_2', $attributes['countries'])->pluck('id');

        $template = HpeContractTemplate::query()->withTrashed()->whereKey($attributes['id'])->firstOrNew();

        $images = ThumbnailManager::retrieveLogoFromModels(true, false, false, $company, $vendor);

        $formData = $this->parseDesign($this->design, $images);

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

    protected function parseDesign(string $design, array $attributes): array
    {
        $design = preg_replace_callback('/{{(.*)}}/m', fn ($item) => data_get($attributes, last($item)), $design);

        return json_decode($design, true);
    }
}
