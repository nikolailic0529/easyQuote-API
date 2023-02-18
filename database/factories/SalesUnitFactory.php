<?php

namespace Database\Factories;

use App\Domain\SalesUnit\Models\SalesUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SalesUnitFactory extends Factory
{
    protected $model = SalesUnit::class;

    public function definition(): array
    {
        return [
            'unit_name' => Str::random(40),
        ];
    }
}
