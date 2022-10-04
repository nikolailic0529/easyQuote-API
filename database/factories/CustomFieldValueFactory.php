<?php

namespace Database\Factories;

use App\Models\System\CustomFieldValue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustomFieldValueFactory extends Factory
{
    protected $model = CustomFieldValue::class;

    public function definition(): array
    {
        return [
            'field_value' => Str::random(40),
        ];
    }
}

