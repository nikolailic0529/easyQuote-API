<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Template\SalesOrderTemplate;
use App\Models\Template\TemplateSchema;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesOrderTemplateFactory extends Factory
{
    protected $model = SalesOrderTemplate::class;

    public function definition(): array
    {
        $templateSchema = factory(TemplateSchema::class)->create([
            'data_headers' => array_map(function (array $header) {
                return $header['value'];
            }, __('template.sales_order_data_headers'))
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

