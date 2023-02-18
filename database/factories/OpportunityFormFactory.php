<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Worldwide\Models\OpportunityForm;
use Faker\Generator as Faker;

$factory->define(OpportunityForm::class, function (Faker $faker) {
    $pipeline = factory(\App\Domain\Pipeline\Models\Pipeline::class)->create();
    $opportunityFormSchema = factory(\App\Domain\Worldwide\Models\OpportunityFormSchema::class)->create();

    return [
        'pipeline_id' => $pipeline->getKey(),
        'form_schema_id' => $opportunityFormSchema->getKey(),
    ];
});
