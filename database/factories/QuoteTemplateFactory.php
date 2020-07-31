<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Company;
use Faker\Generator as Faker;
use App\Models\QuoteTemplate\QuoteTemplate;
use App\Models\QuoteTemplate\TemplateDesign;
use App\Models\Vendor;
use App\Services\ThumbnailManager;

$factory->define(QuoteTemplate::class, function (Faker $faker) {
    $company = Company::whereType(Company::INT_TYPE)->first();
    $vendor = Vendor::where('is_system', true)->first();

    $design = file_get_contents(database_path('seeds/models/template_designs.json'));

    $attributes = ThumbnailManager::retrieveLogoFromModels(true, false, false, $company, $vendor);

    $form = TemplateDesign::parseTemplateDesign($design, $attributes);

    return [
        'name' => $faker->text(100),
        'company_id' => $company->getKey(),
        'vendor_id' => $vendor->getKey(),
        'form_data' => $form
    ];
});
