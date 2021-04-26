<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Company;
use App\Models\Data\Country;
use App\Models\Template\SalesOrderTemplate;
use App\Models\Template\TemplateSchema;
use App\Models\Vendor;
use Faker\Generator as Faker;

$factory->define(SalesOrderTemplate::class, function (Faker $faker) {
    $templateSchema = factory(TemplateSchema::class)->create([
        'data_headers' => __('template.contract_data_headers')
    ]);

    return [
        'template_schema_id' => $templateSchema->getKey(),
        'name' => $faker->text(100),
        'business_division_id' => BD_WORLDWIDE,
        'contract_type_id' => CT_CONTRACT,
        'company_id' => Company::query()->where('is_system', true)->value('id'),
        'vendor_id' => Vendor::query()->where('is_system', true)->value('id'),
    ];
});

$factory->afterCreating(SalesOrderTemplate::class, function (SalesOrderTemplate $salesOrderTemplate) {
    $salesOrderTemplate->countries()->sync(Country::query()->limit(2)->pluck('id'));
});
