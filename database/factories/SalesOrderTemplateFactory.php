<?php

namespace Database\Factories;

use App\Domain\Company\Models\Company;
use App\Domain\Template\Models\TemplateSchema;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Worldwide\Models\SalesOrderTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesOrderTemplateFactory extends Factory
{
    protected $model = SalesOrderTemplate::class;

    public function definition(): array
    {
        $templateSchema = factory(TemplateSchema::class)->create([
            'data_headers' => array_map(function (array $header) {
                return $header['value'];
            }, __('template.sales_order_data_headers')),
        ]);

        return [
            'template_schema_id' => $templateSchema->getKey(),
            'name' => $this->faker->text(100),
            'business_division_id' => BD_WORLDWIDE,
            'contract_type_id' => CT_CONTRACT,
            'company_id' => Company::query()->whereSystem()->value('id'),
            'vendor_id' => Vendor::query()->where('is_system', true)->value('id'),
        ];
    }
}
