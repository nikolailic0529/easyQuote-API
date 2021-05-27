<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\OpportunityForm\OpportunityForm;
use Faker\Generator as Faker;

$factory->define(OpportunityForm::class, function (Faker $faker) {
    $pipeline = factory(\App\Models\Pipeline\Pipeline::class)->create();
    $opportunityFormSchema = factory(\App\Models\OpportunityForm\OpportunityFormSchema::class)->create();

    return [
        'pipeline_id' => $pipeline->getKey(),
        'form_schema_id' => $opportunityFormSchema->getKey(),
    ];
});
