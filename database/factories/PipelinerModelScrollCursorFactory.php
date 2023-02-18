<?php

namespace Database\Factories;

use App\Domain\Pipeliner\Models\PipelinerModelScrollCursor;
use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Database\Eloquent\Factories\Factory;

class PipelinerModelScrollCursorFactory extends Factory
{
    protected $model = PipelinerModelScrollCursor::class;

    public function definition(): array
    {
        return [
            'model_type' => (new Opportunity())->getMorphClass(),
            'cursor' => base64_encode(json_encode([
                $this->faker->dateTimeBetween('now', '+30day')->format(DATE_ATOM), $this->faker->uuid(),
            ])),
        ];
    }
}
