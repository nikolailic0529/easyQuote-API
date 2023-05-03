<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Company\Models\Company;
use App\Domain\Image\Services\ThumbHelper;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\Template\Models\TemplateForm;
use App\Domain\Vendor\Models\Vendor;
use Faker\Generator as Faker;

$factory->define(QuoteTemplate::class, function (Faker $faker) {
    $company = Company::whereType(\App\Domain\Company\Enum\CompanyType::INTERNAL)->first();
    $vendor = Vendor::where('is_system', true)->first();

    $design = file_get_contents(database_path('seeders/models/template_designs.json'));

    $attributes = ThumbHelper::retrieveLogoFromModels(array_filter([$company, $vendor]), ThumbHelper::MAP);

    $form = TemplateForm::parseTemplateDesign($design, $attributes);

    return [
        'name' => $faker->text(80),
        'business_division_id' => '45fc3384-27c1-4a44-a111-2e52b072791e', // Rescue
        'contract_type_id' => 'c3c9d470-cb8b-48a2-9d3f-3614534b24a3', // Services Contract
        'company_id' => $company->getKey(),
        'vendor_id' => $vendor->getKey(),
        'form_data' => $form,
    ];
});
