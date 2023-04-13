<?php

namespace Database\Factories;

use App\Domain\Pipeline\Models\Pipeline;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PipelineFactory extends Factory
{
    protected $model = Pipeline::class;

    public function definition(): array
    {
        return [
            'space_id' => SP_EPD,
            'pipeline_name' => Str::random(40),
        ];
    }
}
