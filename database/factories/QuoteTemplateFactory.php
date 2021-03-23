<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Company;
use Faker\Generator as Faker;
use App\Models\Template\QuoteTemplate;
use App\Models\Template\TemplateSchema;
use App\Models\Vendor;
use App\Services\ThumbHelper;

$factory->define(QuoteTemplate::class, function (Faker $faker) {
    $company = Company::whereType(Company::INT_TYPE)->first();
    $vendor = Vendor::where('is_system', true)->first();

    $design = file_get_contents(database_path('seeders/models/template_designs.json'));

    $attributes = ThumbHelper::retrieveLogoFromModels(array_filter([$company, $vendor]), ThumbHelper::WITH_KEYS);

    $form = TemplateSchema::parseTemplateDesign($design, $attributes);

    return [
        'name' => $faker->text(80),
        'business_division_id' => '45fc3384-27c1-4a44-a111-2e52b072791e', // Rescue
        'contract_type_id' => 'c3c9d470-cb8b-48a2-9d3f-3614534b24a3', // Services Contract
        'company_id' => $company->getKey(),
        'vendor_id' => $vendor->getKey(),
        'form_data' => $form
    ];
});
